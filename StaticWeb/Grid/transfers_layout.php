<?php
require_once ("IncDbMySQL.php");
$db = new Database("test", "root", "password");
session_start();
header("Content-Type: text/xml; charset=utf-8");
header("Cache-Control: max-age=1; must-revalidate");
$parenttable = 'transfers';
$childtable = 'transfers_items';

$XML = array_key_exists("TGData", $_REQUEST) ? $_REQUEST["TGData"] : "";
if ($XML)
{
    // --- simple xml or php xml ---
    $SXML = is_callable("simplexml_load_string");
    if (!$SXML) require_once ("config/Xml.php");
    if ($SXML)
    {
        $Xml = simplexml_load_string(html_entity_decode($XML));
        $AI = $Xml
            ->Changes->I;
    }
    else
    {
        $Xml = CreateXmlFromString(html_entity_decode($XML));
        $AI = $Xml->getElementsByTagName($Xml->documentElement, "I");
    }
    $getparentid = array();
    $getdestinationuuid = array();
    $getdestinationapprove = array();
    $getwarehousenamearray = array();
    $getwarehousecodearray = array();
    $getoriginuuid = array();
    $blockparent = array();
    $updatequery = array();
    // $deletearray = array();

    $newaddedrow = array();
    $newaddedrowchild = array();
    $alreadyupdated = array();
    $diserror = '';
    $dischanges = '';
    $disnewaddedrow = '';
    $allowcalculate = array();
    $updateoninsert = array();

    $updatequery['nocolorid'] = array();
    $outputarray = array();


    foreach($AI as $I){
        $A = $SXML ? $I->attributes() : $Xml->attributes[$I];
        $outresult = $db->xml2array($A)['@attributes'];
        if(isset($outresult["Changed"])) {
            $outputarray[$outresult['id']] = $outresult;
        }
    }

    foreach ($AI as $I)
    {
        $A = $SXML ? $I->attributes() : $Xml->attributes[$I];
        // --- end of simple xml or php xml ---
        if (!empty($A["Deleted"]))
        {
            if (substr((string)$A['id'], 0, 5) != "item-")
            {
                // $deletearray['parent'][] = (string)$A['id'];
                $mainid = (string)$A['id'];
                $updatequery['updatequery'][$mainid][] = 'DELETE FROM '.$parenttable.' WHERE id=\'' . $mainid . '\' ';
            }
            else
            {
                $deleteid = str_replace("item-", "", (string)$A['id']);
                $itemdetail = $db->getparentdetail($deleteid, $childtable);
                $parentid = $itemdetail[0]['Parent'];

                $getparentrows = "SELECT warehouse_destination_uuid, warehouse_origin_uuid, warehouseman_destination_approve FROM ".$parenttable." WHERE id = '" . $parentid . "'";
                $getparentrows = $db->Query($getparentrows);
                $getparentrows = $getparentrows->GetRows();

                $origin = (string)$getparentrows[0]['warehouse_origin_uuid'];

                $destination_uuid = (string)$getparentrows[0]['warehouse_destination_uuid'];

                $destinationapprove = (string)$getparentrows[0]['warehouseman_destination_approve'];

                if ($destinationapprove == 1)
                {
                    $getallchild = $db->getoriginalparentdata($parentid, 'id, item_tempory', $childtable);
                    foreach ($getallchild as $getallchildkey => $getallchildvalue) {
                        if($getallchildvalue['item_tempory']) {
                            $destinationapprove = 0;
                        }
                    }
                }

                $itemuuid = $itemdetail[0]["item_uuid"];
                $itemquantity = $itemdetail[0]["item_quantity"];
                $updatequery['deleted'][$deleteid] = $itemuuid;
                if ($destinationapprove == 1)
                {

                    if(isset($updatequery['temp_newqty'][$destination_uuid][$itemuuid])) {
                        $destination_newqty = $updatequery['temp_newqty'][$destination_uuid][$itemuuid] - $itemquantity;
                    } else {
                        $destination_newqty = $db->getquantitydifference($itemquantity, 'item_uuid = '.$itemuuid.' AND warehouse_uuid = '.$destination_uuid);
                    }

                    //
                    if($destination_newqty >= 0) {
                        $updatequery['temp_newqty'][$destination_uuid][$itemuuid] = $destination_newqty;
                        $updatequery['upload'][$parentid][$itemuuid]['warehouse_destination'] = $destination_uuid;
                        $updatequery['upload'][$parentid][$itemuuid]['qty'] = $destination_newqty;
                        $updatequery['upload'][$parentid][$itemuuid]['itemuuid'] = $itemuuid;

                        if(isset($updatequery['temp_item_data'][$destination_uuid][$itemuuid])) {
                            $item_data_func = $db->get_item_stock_value_delete($childtable, $deleteid, $itemuuid, $destination_uuid, $itemquantity, true, true, json_encode($updatequery['temp_item_data'][$destination_uuid][$itemuuid]), $destination_newqty);
                            $updatequery['temp_item_data'][$destination_uuid][$itemuuid] = $item_data_func;
                            $updatequery['upload'][$parentid][$itemuuid]['item_data'] = $item_data_func;
                        } else {
                            $item_data_func = $db->get_item_stock_value_delete($childtable, $deleteid, $itemuuid, $destination_uuid, $itemquantity, true, true);
                            $updatequery['temp_item_data'][$destination_uuid][$itemuuid] = $item_data_func;
                            $updatequery['upload'][$parentid][$itemuuid]['item_data'] = $item_data_func;
                        }

                        if(isset($updatequery['temp_newqty'][$origin][$itemuuid])) {
                            $newqty = $updatequery['temp_newqty'][$origin][$itemuuid] + $itemquantity;
                        } else {
                            $newqty = $db->getquantitysum($itemquantity, 'item_uuid = '.$itemuuid.' AND warehouse_uuid = '.$origin);
                        }

                        if($newqty >= 0) {
                            $updatequery['temp_newqty'][$origin][$itemuuid] = $newqty;
                            $updatequery['upload_old'][$parentid][$itemuuid]['warehouse_origin'] = $origin;
                            $updatequery['upload_old'][$parentid][$itemuuid]['qty'] = $newqty;
                            $updatequery['upload_old'][$parentid][$itemuuid]['itemuuid'] = $itemuuid;

                            if(isset($updatequery['temp_item_data'][$origin][$itemuuid])) {
                                $item_data_func = $db->get_item_stock_value_delete($childtable, $deleteid, $itemuuid, $origin, $itemquantity, false, true, json_encode($updatequery['temp_item_data'][$origin][$itemuuid]), $newqty);
                                $updatequery['temp_item_data'][$origin][$itemuuid] = $item_data_func;
                                $updatequery['upload_old'][$parentid][$itemuuid]['item_data'] = $item_data_func;
                            } else {
                                $item_data_func = $db->get_item_stock_value_delete($childtable, $deleteid, $itemuuid, $origin, $itemquantity, false, true);
                                $updatequery['temp_item_data'][$origin][$itemuuid] = $item_data_func;
                                $updatequery['upload_old'][$parentid][$itemuuid]['item_data'] = $item_data_func;
                            }

                        } else {
                            $itemidtodis = (string)$A['id'];

                            $getwarehousename = $db->getwarehousename($parenttable, $parentid, 'id', 'warehouse_origin');
                            $getwarehousecode = $db->getwarehousename($parenttable, $parentid, 'id', 'warehouse_origin_code');

                            $getitemname = $db->getwarehousename($childtable, $deleteid, 'id', 'item_name');
                            $getitemcode = $db->getwarehousename($childtable, $deleteid, 'id', 'item_code');

                            $updatequery = $db->displayerror($updatequery, $getitemcode, $getitemname, $getwarehousename, $getwarehousecode, 'item-'.$deleteid, $parentid);

                            $updatequery['deletebreak']['item-'.$deleteid] = '';
                            $updatequery['deletebreak'][$parentid] = '';
                            $updatequery['break'][$parentid] = '';
                            continue;
                        }

                    }
                    else
                    {

                        $itemidtodis = (string)$A['id'];

                        $getwarehousename = $db->getwarehousename($parenttable, $parentid, 'id', 'warehouse_origin');
                        $getwarehousecode = $db->getwarehousename($parenttable, $parentid, 'id', 'warehouse_origin_code');

                        $getitemname = $db->getwarehousename($childtable, $deleteid, 'id', 'item_name');
                        $getitemcode = $db->getwarehousename($childtable, $deleteid, 'id', 'item_code');

                        $updatequery = $db->displayerror($updatequery, $getitemcode, $getitemname, $getwarehousename, $getwarehousecode, 'item-'.$deleteid, $parentid);

                        $updatequery['deletebreak']['item-'.$deleteid] = '';
                        $updatequery['deletebreak'][$parentid] = '';
                        $updatequery['break'][$parentid] = '';
                        continue;
                    }
                }
                $updatequery['updatequery'][$parentid][] = 'DELETE FROM '.$childtable.' WHERE id=\'' . $deleteid . '\' ';
                // else
                // {
                //     $deletearray['itemquantity'][] = '';
                //     $deletearray['uuid'][] = '';
                //     $deletearray['origin'][] = '';
                // }
                // $deletearray['item'][] = $deleteid;
                // $deletearray['itemparent'][] = $parentid;
            }
        }
    }

    foreach ($outputarray as $key => $value) {
        if (substr($key, 0, 5) != "item-") {
            $parent = 1;
            $postid = $key;
            $parentid = $postid;
        } else {
            $parent = 0;
            $postid = str_replace("item-", "", $key);
            $getchilddata = $db->getoriginaldata($postid, '*', $childtable)[0];
            $parentid = $getchilddata['Parent'];
        }

        $getallchild = $db->getoriginalparentdata($parentid, 'id, item_tempory, item_quantity', $childtable);
        foreach ($getallchild as $getallchildkey => $getallchildvalue) {
            if($getallchildvalue['item_tempory'] == 1) {
                if($postid == $getallchildvalue['id']) {
                    // echo 'test';
                    $allowcalculate['item-'.$postid][$parentid] = 'update_this';
                }
                $allowcalculate[$parentid]['item-'.$getallchildvalue['id']] = $getallchildvalue['item_quantity'];
            }
        }
        if(isset($allowcalculate['item-'.$postid])) {
            if(isset($allowcalculate['item-'.$postid][$parentid])) {
                foreach ($allowcalculate[$parentid] as $key => $value) {
                    if(!isset($outputarray[$key])) {
                        $outputarray[$key] = array('id' => $key, 'Changed' => '1', 'item_quantity' => $value);
                    }
                }
            }
        }
    }

    foreach ($outputarray as $key => $value) {
        // If child or parent
        if (substr($key, 0, 5) != "item-") {
            $parent = 1;
            $postid = $key;
            $S = "UPDATE ".$parenttable." SET ";
            $parentid = $postid;
        } else {
            $parent = 0;
            $postid = str_replace("item-", "", $key);
            $S = "UPDATE ".$childtable." SET ";
            $getchilddata = $db->getoriginaldata($postid, '*', $childtable)[0];
            $parentid = $getchilddata['Parent'];
            if($getchilddata['item_tempory'] == 1) {
                $value['item_tempory'] = 0;
            }
        }

        $islastrow = 1;

        foreach ($value as $updatekey => $updatevalue) {
            if($updatekey != 'Changed' AND $updatekey != 'id') {
                    $S .= "`".$updatekey."` = '".$updatevalue."'";
                if(count($value) != $islastrow) {
                    $S .= ",";
                }
            }
            $islastrow++;
        }
        $S .= " WHERE id='" . $postid . "'";
        $updatequery['updatequery'][$parentid][] = $S;


        if($parent) {
            // If updateing Parent
            $getparentdata = $db->getoriginaldata($postid, '*', $parenttable)[0];
            $warehouse_name = $getparentdata['warehouse_origin'];
            $warehouse_code = $getparentdata['warehouse_origin_code'];

            if(isset($value['warehouseman_destination_approve'])) {
                $approveupdate = 1;
                $warehouseman_destination_approve = $value['warehouseman_destination_approve'];
            } else {
                $approveupdate = 0;
                $warehouseman_destination_approve = $getparentdata['warehouseman_destination_approve'];
            }
            if(isset($value['warehouse_origin_uuid'])) {
                $old_warehouse_origin_uuid = $getparentdata['warehouse_origin_uuid'];
                $warehouseupdate = 1;
                $warehouse_origin_uuid = $value['warehouse_origin_uuid'];
            } else {
                $old_warehouse_origin_uuid = $getparentdata['warehouse_origin_uuid'];
                $warehouseupdate = 0;
                $warehouse_origin_uuid = $getparentdata['warehouse_origin_uuid'];
            }

            if(isset($value['warehouse_destination_uuid'])) {
                $old_warehouse_destination_uuid = $getparentdata['warehouse_destination_uuid'];
                $warehouse_destination_update = 1;
                $warehouse_destination_uuid = $value['warehouse_destination_uuid'];
            } else {
                $old_warehouse_destination_uuid = $getparentdata['warehouse_destination_uuid'];
                $warehouse_destination_update = 0;
                $warehouse_destination_uuid = $getparentdata['warehouse_destination_uuid'];
            }

            $getchildrows = $db->getoriginalparentdata($postid, '*', $childtable);
            if(count($getchildrows) > 0) {
                foreach ($getchildrows as $childkey => $childvalue) {
                    $item_id = $childvalue['id'];
                    $item_name = $childvalue['item_name'];
                    $item_code = $childvalue['item_code'];

                    if(isset($outputarray['item-'.$item_id]['item_uuid'])) {
                        $old_item_uuid = $childvalue['item_uuid'];
                        $itemuuid_update = 1;
                        $item_uuid = $outputarray['item-'.$item_id]['item_uuid'];
                    } else {
                        $old_item_uuid = $childvalue['item_uuid'];
                        $itemuuid_update = 0;
                        $item_uuid = $childvalue['item_uuid'];
                    }

                    if(isset($outputarray['item-'.$item_id]['item_quantity'])) {
                        $old_item_quantity = $childvalue['item_quantity'];
                        $itemqty_update = 1;
                        $item_quantity = $outputarray['item-'.$item_id]['item_quantity'] + $old_item_quantity;
                        if($warehouseupdate || $approveupdate || $itemuuid_update || $warehouse_destination_update) {
                            $item_quantity = $outputarray['item-'.$item_id]['item_quantity'];
                        }
                    } else {
                        $old_item_quantity = $childvalue['item_quantity'];
                        $itemqty_update = 0;
                        $item_quantity = $childvalue['item_quantity'];
                    }

                    if(isset($updatequery['deleted'][$childvalue['id']])) {
                        continue;
                    }
                    if($warehouseman_destination_approve) {
                        $updatequery['updatequery'][$postid][] = "UPDATE ".$childtable." SET `item_tempory` = 0 WHERE `id` = '" . $item_id . "'";
                        if($approveupdate) {
                            if(isset($updatequery['temp_newqty'][$warehouse_origin_uuid][$item_uuid])) {
                                $newqty_transfer = $updatequery['temp_newqty'][$warehouse_origin_uuid][$item_uuid] - $old_item_quantity;
                            } else {
                                $newqty_transfer = $db->getquantitydifference($item_quantity, 'item_uuid = '.$item_uuid.' AND warehouse_uuid = '.$warehouse_origin_uuid);
                            }

                            if(isset($updatequery['temp_newqty'][$warehouse_destination_uuid][$item_uuid])) {
                                $newqty = $updatequery['temp_newqty'][$warehouse_destination_uuid][$item_uuid] + $item_quantity;
                            } else {
                                $newqty = $db->getquantitysum($item_quantity, 'item_uuid = '.$item_uuid.' AND warehouse_uuid = '.$warehouse_destination_uuid);
                            }

                            if($newqty >= 0 AND $newqty_transfer >= 0) {
                                $updatequery['temp_newqty'][$warehouse_origin_uuid][$item_uuid] = $newqty_transfer;
                                $updatequery['upload'][$postid][$item_uuid]['warehouse_origin'] = $warehouse_origin_uuid;
                                $updatequery['upload'][$postid][$item_uuid]['qty'] = $newqty_transfer;
                                $updatequery['upload'][$postid][$item_uuid]['itemuuid'] = $item_uuid;

                                if(isset($updatequery['temp_item_data'][$warehouse_origin_uuid][$item_uuid])) {
                                    $item_data_func = $db->get_item_stock_value_parent($childtable, $item_id, $item_uuid, $warehouse_origin_uuid, $item_quantity, true, false, json_encode($updatequery['temp_item_data'][$warehouse_origin_uuid][$item_uuid]), $newqty_transfer);
                                    $updatequery['temp_item_data'][$warehouse_origin_uuid][$item_uuid] = $item_data_func;
                                    $updatequery['upload'][$postid][$item_uuid]['item_data'] = $item_data_func;
                                } else {
                                    $item_data_func = $db->get_item_stock_value_parent($childtable, $item_id, $item_uuid, $warehouse_origin_uuid, $item_quantity, true, false);
                                    $updatequery['temp_item_data'][$warehouse_origin_uuid][$item_uuid] = $item_data_func;
                                    $updatequery['upload'][$postid][$item_uuid]['item_data'] = $item_data_func;
                                }

                                $updatequery['temp_newqty'][$warehouse_destination_uuid][$item_uuid] = $newqty;
                                $updatequery['upload_old'][$postid][$item_uuid]['warehouse_destination'] = $warehouse_destination_uuid;
                                $updatequery['upload_old'][$postid][$item_uuid]['qty'] = $newqty;
                                $updatequery['upload_old'][$postid][$item_uuid]['itemuuid'] = $item_uuid;

                                if(isset($updatequery['temp_item_data'][$destination_uuid][$itemuuid])) {
                                    $item_data_func = $db->get_item_stock_value_parent($childtable, $item_id, $item_uuid, $warehouse_destination_uuid, $item_quantity, false, false, json_encode($updatequery['temp_item_data'][$warehouse_destination_uuid][$item_uuid]), $newqty);
                                    $updatequery['temp_item_data'][$warehouse_destination_uuid][$itemuuid] = $item_data_func;
                                    $updatequery['upload_old'][$postid][$item_uuid]['item_data'] = $item_data_func;
                                } else {
                                    $item_data_func = $db->get_item_stock_value_parent($childtable, $item_id, $item_uuid, $warehouse_destination_uuid, $item_quantity, false, false);
                                    $updatequery['temp_item_data'][$warehouse_destination_uuid][$item_uuid] = $item_data_func;
                                    $updatequery['upload_old'][$postid][$item_uuid]['item_data'] = $item_data_func;
                                }

                            } else {
                                $updatequery = $db->displayerror($updatequery, $item_code, $item_name, $warehouse_name, $warehouse_code, 'item-'.$item_id, $postid);
                            }
                        } else {
                            if($warehouseupdate) {
                                if($itemuuid_update) {

                                    $temp_qty = 0;
                                    if(isset($updatequery['temp_newqty'][$warehouse_origin_uuid][$item_uuid])) {
                                        $temp_qty = $updatequery['temp_newqty'][$warehouse_origin_uuid][$item_uuid];
                                        $newqty = $updatequery['temp_newqty'][$warehouse_origin_uuid][$item_uuid] - $item_quantity;
                                    } else {
                                        $newqty = $db->getquantitydifference($item_quantity, 'item_uuid = '.$item_uuid.' AND warehouse_uuid = '.$warehouse_origin_uuid);
                                    }

                                    $temp_qty_2 = 0;
                                    if(isset($updatequery['temp_newqty'][$old_warehouse_origin_uuid][$old_item_uuid])) {
                                        $temp_qty_2 = $updatequery['temp_newqty'][$old_warehouse_origin_uuid][$old_item_uuid];
                                        $newqty_2 = $updatequery['temp_newqty'][$old_warehouse_origin_uuid][$old_item_uuid] + $old_item_quantity;
                                    } else {
                                        $newqty_2 = $db->getquantitysum($old_item_quantity, 'item_uuid = '.$old_item_uuid.' AND warehouse_uuid = '.$old_warehouse_origin_uuid);
                                    }

                                    if($newqty >= 0 AND $newqty_2 >= 0) {

                                        $updatequery['temp_newqty'][$warehouse_origin_uuid][$item_uuid] = $newqty;
                                        $updatequery['upload'][$postid][$item_uuid]['warehouse_origin'] = $warehouse_origin_uuid;
                                        $updatequery['upload'][$postid][$item_uuid]['qty'] = $newqty;
                                        $updatequery['upload'][$postid][$item_uuid]['itemuuid'] = $item_uuid;

                                        if(isset($updatequery['temp_item_data'][$warehouse_origin_uuid][$item_uuid])) {
                                            $item_data_func = $db->get_item_stock_value_parent($childtable, $item_id, $item_uuid, $warehouse_origin_uuid, $item_quantity, true, false, json_encode($updatequery['temp_item_data'][$warehouse_origin_uuid][$item_uuid]), $newqty);
                                            $updatequery['temp_item_data'][$warehouse_origin_uuid][$item_uuid] = $item_data_func;
                                            $updatequery['upload'][$postid][$item_uuid]['item_data'] = $item_data_func;
                                        } else {
                                            $item_data_func = $db->get_item_stock_value_parent($childtable, $item_id, $item_uuid, $warehouse_origin_uuid, $item_quantity, true, false);
                                            $updatequery['temp_item_data'][$warehouse_origin_uuid][$item_uuid] = $item_data_func;
                                            $updatequery['upload'][$postid][$item_uuid]['item_data'] = $item_data_func;
                                        }

                                        $updatequery['temp_newqty'][$old_warehouse_origin_uuid][$old_item_uuid] = $newqty_2;
                                        $updatequery['upload_old'][$postid][$old_item_uuid]['warehouse_origin'] = $old_warehouse_origin_uuid;
                                        $updatequery['upload_old'][$postid][$old_item_uuid]['qty'] = $newqty_2;
                                        $updatequery['upload_old'][$postid][$old_item_uuid]['itemuuid'] = $old_item_uuid;

                                        if(isset($updatequery['temp_item_data'][$old_warehouse_origin_uuid][$old_item_uuid])) {
                                            $item_data_func = $db->get_item_stock_value_parent($childtable, $item_id, $old_item_uuid, $old_warehouse_origin_uuid, $old_item_quantity, false, false, json_encode($updatequery['temp_item_data'][$old_warehouse_origin_uuid][$old_item_uuid]), $newqty_2);
                                            $updatequery['temp_item_data'][$old_warehouse_origin_uuid][$old_item_uuid] = $item_data_func;
                                            $updatequery['upload_old'][$postid][$old_item_uuid]['item_data'] = $item_data_func;
                                        } else {
                                            $item_data_func = $db->get_item_stock_value_parent($childtable, $item_id, $old_item_uuid, $old_warehouse_origin_uuid, $old_item_quantity, false, false);
                                            $updatequery['temp_item_data'][$old_warehouse_origin_uuid][$old_item_uuid] = $item_data_func;
                                            $updatequery['upload_old'][$postid][$old_item_uuid]['item_data'] = $item_data_func;
                                        }

                                    } else {
                                        $updatequery = $db->displayerror($updatequery, $item_code, $item_name, $warehouse_name, $warehouse_code, 'item-'.$item_id, $postid);
                                    }

                                } else {

                                    $temp_qty = 0;
                                    if(isset($updatequery['temp_newqty'][$warehouse_origin_uuid][$item_uuid])) {
                                        $temp_qty = $updatequery['temp_newqty'][$warehouse_origin_uuid][$item_uuid];
                                        $newqty = $updatequery['temp_newqty'][$warehouse_origin_uuid][$item_uuid] - $item_quantity;
                                    } else {
                                        $newqty = $db->getquantitydifference($item_quantity, 'item_uuid = '.$item_uuid.' AND warehouse_uuid = '.$warehouse_origin_uuid);
                                    }

                                    $temp_qty_2 = 0;
                                    if(isset($updatequery['temp_newqty'][$old_warehouse_origin_uuid][$item_uuid])) {
                                        $temp_qty_2 = $updatequery['temp_newqty'][$warehouse_origin_uuid][$item_uuid];
                                        $newqty_2 = $updatequery['temp_newqty'][$old_warehouse_origin_uuid][$item_uuid] + $old_item_quantity;
                                    } else {
                                        $newqty_2 = $db->getquantitysum($old_item_quantity, 'item_uuid = '.$item_uuid.' AND warehouse_uuid = '.$old_warehouse_origin_uuid);
                                    }

                                    if($newqty >= 0 AND $newqty_2 >= 0) {
                                        $updatequery['temp_newqty'][$warehouse_origin_uuid][$item_uuid] = $newqty;
                                        $updatequery['upload'][$postid][$item_uuid]['warehouse_origin'] = $warehouse_origin_uuid;
                                        $updatequery['upload'][$postid][$item_uuid]['qty'] = $newqty;
                                        $updatequery['upload'][$postid][$item_uuid]['itemuuid'] = $item_uuid;

                                        if(isset($updatequery['temp_item_data'][$warehouse_origin_uuid][$item_uuid])) {
                                            $item_data_func = $db->get_item_stock_value_parent($childtable, $item_id, $item_uuid, $warehouse_origin_uuid, $item_quantity, true, false, json_encode($updatequery['temp_item_data'][$warehouse_origin_uuid][$item_uuid]), $newqty);
                                            $updatequery['temp_item_data'][$warehouse_origin_uuid][$item_uuid] = $item_data_func;
                                            $updatequery['upload'][$postid][$item_uuid]['item_data'] = $item_data_func;
                                        } else {
                                            $item_data_func = $db->get_item_stock_value_parent($childtable, $item_id, $item_uuid, $warehouse_origin_uuid, $item_quantity, true, false);
                                            $updatequery['temp_item_data'][$warehouse_origin_uuid][$item_uuid] = $item_data_func;
                                            $updatequery['upload'][$postid][$item_uuid]['item_data'] = $item_data_func;
                                        }

                                        $updatequery['temp_newqty'][$old_warehouse_origin_uuid][$item_uuid] = $newqty_2;
                                        $updatequery['upload_old'][$postid][$item_uuid]['warehouse_origin'] = $old_warehouse_origin_uuid;
                                        $updatequery['upload_old'][$postid][$item_uuid]['qty'] = $newqty_2;
                                        $updatequery['upload_old'][$postid][$item_uuid]['itemuuid'] = $item_uuid;

                                        if(isset($updatequery['temp_item_data'][$old_warehouse_origin_uuid][$item_uuid])) {
                                            $item_data_func = $db->get_item_stock_value_parent($childtable, $item_id, $item_uuid, $old_warehouse_origin_uuid, $old_item_quantity, false, false, json_encode($updatequery['temp_item_data'][$old_warehouse_origin_uuid][$item_uuid]), $newqty_2);
                                            $updatequery['temp_item_data'][$old_warehouse_origin_uuid][$item_uuid] = $item_data_func;
                                            $updatequery['upload_old'][$postid][$item_uuid]['item_data'] = $item_data_func;
                                        } else {
                                            $item_data_func = $db->get_item_stock_value_parent($childtable, $item_id, $item_uuid, $old_warehouse_origin_uuid, $old_item_quantity, false, false);
                                            $updatequery['temp_item_data'][$old_warehouse_origin_uuid][$item_uuid] = $item_data_func;
                                            $updatequery['upload_old'][$postid][$item_uuid]['item_data'] = $item_data_func;
                                        }

                                    } else {
                                        $updatequery = $db->displayerror($updatequery, $item_code, $item_name, $warehouse_name, $warehouse_code, 'item-'.$item_id, $postid);
                                    }

                                }

                            }
                            if($warehouse_destination_update) {
                                if($itemuuid_update) {
                                    $temp_qty = 0;
                                    if(isset($updatequery['temp_newqty'][$warehouse_destination_uuid][$item_uuid])) {
                                        $temp_qty = $updatequery['temp_newqty'][$warehouse_destination_uuid][$item_uuid];
                                        $newqty = $updatequery['temp_newqty'][$warehouse_destination_uuid][$item_uuid] + $item_quantity;
                                    } else {
                                        $newqty = $db->getquantitysum($item_quantity, 'item_uuid = '.$item_uuid.' AND warehouse_uuid = '.$warehouse_destination_uuid);
                                    }

                                    $temp_qty_2 = 0;
                                    if(isset($updatequery['temp_newqty'][$old_warehouse_destination_uuid][$old_item_uuid])) {
                                        $temp_qty_2 = $updatequery['temp_newqty'][$old_warehouse_destination_uuid][$old_item_uuid];
                                        $newqty_2 = $updatequery['temp_newqty'][$old_warehouse_destination_uuid][$old_item_uuid] - $old_item_quantity;
                                    } else {
                                        $newqty_2 = $db->getquantitydifference($old_item_quantity, 'item_uuid = '.$old_item_uuid.' AND warehouse_uuid = '.$old_warehouse_destination_uuid);
                                    }

                                    if($newqty >= 0 AND $newqty_2 >= 0) {

                                        $updatequery['temp_newqty'][$warehouse_destination_uuid][$item_uuid] = $newqty;
                                        $updatequery['upload_transfer'][$postid][$item_uuid]['warehouse_destination'] = $warehouse_destination_uuid;
                                        $updatequery['upload_transfer'][$postid][$item_uuid]['qty'] = $newqty;
                                        $updatequery['upload_transfer'][$postid][$item_uuid]['itemuuid'] = $item_uuid;

                                        if(isset($updatequery['temp_item_data'][$warehouse_destination_uuid][$item_uuid])) {
                                            $item_data_func = $db->get_item_stock_value_parent($childtable, $item_id, $item_uuid, $warehouse_destination_uuid, $item_quantity, false, false, json_encode($updatequery['temp_item_data'][$warehouse_destination_uuid][$item_uuid]), $newqty);
                                            $updatequery['temp_item_data'][$warehouse_destination_uuid][$item_uuid] = $item_data_func;
                                            $updatequery['upload_transfer'][$postid][$item_uuid]['item_data'] = $item_data_func;
                                        } else {
                                            $item_data_func = $db->get_item_stock_value_parent($childtable, $item_id, $item_uuid, $warehouse_destination_uuid, $item_quantity, false, false);
                                            $updatequery['temp_item_data'][$warehouse_destination_uuid][$item_uuid] = $item_data_func;
                                            $updatequery['upload_transfer'][$postid][$item_uuid]['item_data'] = $item_data_func;
                                        }

                                        $updatequery['temp_newqty'][$old_warehouse_destination_uuid][$old_item_uuid] = $newqty_2;
                                        $updatequery['upload_old_transfer'][$postid][$old_item_uuid]['warehouse_destination'] = $old_warehouse_destination_uuid;
                                        $updatequery['upload_old_transfer'][$postid][$old_item_uuid]['qty'] = $newqty_2;
                                        $updatequery['upload_old_transfer'][$postid][$old_item_uuid]['itemuuid'] = $old_item_uuid;

                                        if(isset($updatequery['temp_item_data'][$old_warehouse_destination_uuid][$itemuuid])) {
                                            $item_data_func = $db->get_item_stock_value_parent($childtable, $item_id, $old_item_uuid, $old_warehouse_destination_uuid, $old_item_quantity, true, false, json_encode($updatequery['temp_item_data'][$old_warehouse_destination_uuid][$itemuuid]), $newqty_2);
                                            $updatequery['temp_item_data'][$old_warehouse_destination_uuid][$itemuuid] = $item_data_func;
                                            $updatequery['upload_old_transfer'][$postid][$old_item_uuid]['item_data'] = $item_data_func;
                                        } else {
                                            $item_data_func = $db->get_item_stock_value_parent($childtable, $item_id, $old_item_uuid, $old_warehouse_destination_uuid, $old_item_quantity, true, false);
                                            $updatequery['temp_item_data'][$old_warehouse_destination_uuid][$old_item_uuid] = $item_data_func;
                                            $updatequery['upload_old_transfer'][$postid][$old_item_uuid]['item_data'] = $item_data_func;
                                        }

                                    } else {
                                        $updatequery = $db->displayerror($updatequery, $item_code, $item_name, $warehouse_name, $warehouse_code, 'item-'.$item_id, $postid);
                                    }

                                } else {

                                    $temp_qty = 0;
                                    if(isset($updatequery['temp_newqty'][$warehouse_destination_uuid][$item_uuid])) {
                                        $temp_qty = $updatequery['temp_newqty'][$warehouse_destination_uuid][$item_uuid];
                                        $newqty = $updatequery['temp_newqty'][$warehouse_destination_uuid][$item_uuid] + $item_quantity;
                                    } else {
                                        $newqty = $db->getquantitysum($item_quantity, 'item_uuid = '.$item_uuid.' AND warehouse_uuid = '.$warehouse_destination_uuid);
                                    }

                                    $temp_qty_2 = 0;
                                    if(isset($updatequery['temp_newqty'][$old_warehouse_destination_uuid][$item_uuid])) {
                                        $temp_qty_2 = $updatequery['temp_newqty'][$old_warehouse_destination_uuid][$item_uuid];
                                        $newqty_2 = $updatequery['temp_newqty'][$old_warehouse_destination_uuid][$item_uuid] - $old_item_quantity;
                                    } else {
                                        $newqty_2 = $db->getquantitydifference($old_item_quantity, 'item_uuid = '.$item_uuid.' AND warehouse_uuid = '.$old_warehouse_destination_uuid);
                                    }

                                    if($newqty >= 0 AND $newqty_2 >= 0) {
                                        $updatequery['temp_newqty'][$warehouse_destination_uuid][$item_uuid] = $newqty;
                                        $updatequery['upload_transfer'][$postid][$item_uuid]['warehouse_destination'] = $warehouse_destination_uuid;
                                        $updatequery['upload_transfer'][$postid][$item_uuid]['qty'] = $newqty;
                                        $updatequery['upload_transfer'][$postid][$item_uuid]['itemuuid'] = $item_uuid;

                                        if(isset($updatequery['temp_item_data'][$warehouse_destination_uuid][$item_uuid])) {
                                            $item_data_func = $db->get_item_stock_value_parent($childtable, $item_id, $item_uuid, $warehouse_destination_uuid, $item_quantity, false, false, json_encode($updatequery['temp_item_data'][$warehouse_destination_uuid][$item_uuid]), $newqty);
                                            $updatequery['temp_item_data'][$warehouse_destination_uuid][$item_uuid] = $item_data_func;
                                            $updatequery['upload_transfer'][$postid][$item_uuid]['item_data'] = $item_data_func;
                                        } else {
                                            $item_data_func = $db->get_item_stock_value_parent($childtable, $item_id, $item_uuid, $warehouse_destination_uuid, $item_quantity, false, false);
                                            $updatequery['temp_item_data'][$warehouse_destination_uuid][$item_uuid] = $item_data_func;
                                            $updatequery['upload_transfer'][$postid][$item_uuid]['item_data'] = $item_data_func;
                                        }

                                        $updatequery['temp_newqty'][$old_warehouse_destination_uuid][$item_uuid] = $newqty_2;
                                        $updatequery['upload_old_transfer'][$postid][$item_uuid]['warehouse_destination'] = $old_warehouse_destination_uuid;
                                        $updatequery['upload_old_transfer'][$postid][$item_uuid]['qty'] = $newqty_2;
                                        $updatequery['upload_old_transfer'][$postid][$item_uuid]['itemuuid'] = $item_uuid;

                                        if(isset($updatequery['temp_item_data'][$old_warehouse_destination_uuid][$item_uuid])) {
                                            $item_data_func = $db->get_item_stock_value_parent($childtable, $item_id, $item_uuid, $old_warehouse_destination_uuid, $old_item_quantity, true, false, json_encode($updatequery['temp_item_data'][$old_warehouse_destination_uuid][$item_uuid]),$newqty_2);
                                            $updatequery['temp_item_data'][$old_warehouse_destination_uuid][$item_uuid] = $item_data_func;
                                            $updatequery['upload_old_transfer'][$postid][$item_uuid]['item_data'] = $item_data_func;
                                        } else {
                                            $item_data_func = $db->get_item_stock_value_parent($childtable, $item_id, $item_uuid, $old_warehouse_destination_uuid, $old_item_quantity, true, false);
                                            $updatequery['temp_item_data'][$old_warehouse_destination_uuid][$item_uuid] = $item_data_func;
                                            $updatequery['upload_old_transfer'][$postid][$item_uuid]['item_data'] = $item_data_func;
                                        }

                                    } else {
                                        $updatequery = $db->displayerror($updatequery, $item_code, $item_name, $warehouse_name, $warehouse_code, 'item-'.$item_id, $postid);
                                    }
                                }
                            }
                            if($itemuuid_update AND !$warehouse_destination_update) {

                                if(isset($updatequery['temp_newqty'][$warehouse_destination_uuid][$item_uuid])) {
                                    $newqty = $updatequery['temp_newqty'][$warehouse_destination_uuid][$item_uuid] + $item_quantity;
                                } else {
                                    $newqty = $db->getquantitysum($item_quantity, 'item_uuid = '.$item_uuid.' AND warehouse_uuid = '.$warehouse_destination_uuid);
                                }

                                if(isset($updatequery['temp_newqty'][$warehouse_destination_uuid][$old_item_uuid])) {
                                    $newqty_2 = $updatequery['temp_newqty'][$warehouse_destination_uuid][$old_item_uuid] - $old_item_quantity;
                                } else {
                                    $newqty_2 = $db->getquantitydifference($old_item_quantity, 'item_uuid = '.$old_item_uuid.' AND warehouse_uuid = '.$warehouse_destination_uuid);
                                }

                                if($newqty >= 0 AND $newqty_2 >= 0) {

                                    $updatequery['temp_newqty'][$warehouse_destination_uuid][$item_uuid] = $newqty;
                                    $updatequery['upload_transfer'][$postid][$item_uuid]['warehouse_destination'] = $warehouse_destination_uuid;
                                    $updatequery['upload_transfer'][$postid][$item_uuid]['qty'] = $newqty;
                                    $updatequery['upload_transfer'][$postid][$item_uuid]['itemuuid'] = $item_uuid;

                                    if(isset($updatequery['temp_item_data'][$warehouse_destination_uuid][$itemuuid])) {
                                        $item_data_func = $db->get_item_stock_value_parent($childtable, $item_id, $item_uuid, $warehouse_destination_uuid, $item_quantity, false, false, json_encode($updatequery['temp_item_data'][$warehouse_destination_uuid][$itemuuid]), $newqty);
                                        $updatequery['temp_item_data'][$warehouse_destination_uuid][$item_uuid] = $item_data_func;
                                        $updatequery['upload_transfer'][$postid][$item_uuid]['item_data'] = $item_data_func;
                                    } else {
                                        $item_data_func = $db->get_item_stock_value_parent($childtable, $item_id, $item_uuid, $warehouse_destination_uuid, $item_quantity, false, false);
                                        $updatequery['temp_item_data'][$warehouse_destination_uuid][$item_uuid] = $item_data_func;
                                        $updatequery['upload_transfer'][$postid][$item_uuid]['item_data'] = $item_data_func;
                                    }

                                    $updatequery['temp_newqty'][$warehouse_destination_uuid][$old_item_uuid] = $newqty_2;
                                    $updatequery['upload_old_transfer'][$postid][$old_item_uuid]['warehouse_destination'] = $warehouse_destination_uuid;
                                    $updatequery['upload_old_transfer'][$postid][$old_item_uuid]['qty'] = $newqty_2;
                                    $updatequery['upload_old_transfer'][$postid][$old_item_uuid]['itemuuid'] = $old_item_uuid;

                                    if(isset($updatequery['temp_item_data'][$warehouse_destination_uuid][$old_item_uuid])) {
                                        $item_data_func = $db->get_item_stock_value_parent($childtable, $item_id, $old_item_uuid, $warehouse_destination_uuid, $old_item_quantity, true, false, json_encode($updatequery['temp_item_data'][$warehouse_destination_uuid][$old_item_uuid]), $newqty_2);
                                        $updatequery['temp_item_data'][$warehouse_destination_uuid][$old_item_uuid] = $item_data_func;
                                        $updatequery['upload_old_transfer'][$postid][$old_item_uuid]['item_data'] = $item_data_func;
                                    } else {
                                        $item_data_func = $db->get_item_stock_value_parent($childtable, $item_id, $old_item_uuid, $warehouse_destination_uuid, $old_item_quantity, true, false);
                                        $updatequery['temp_item_data'][$warehouse_destination_uuid][$old_item_uuid] = $item_data_func;
                                        $updatequery['upload_old_transfer'][$postid][$old_item_uuid]['item_data'] = $item_data_func;
                                    }

                                } else {
                                    $updatequery = $db->displayerror($updatequery, $item_code, $item_name, $warehouse_name, $warehouse_code, 'item-'.$item_id, $postid);
                                }

                            } elseif($itemuuid_update AND !$warehouseupdate) {

                                if(isset($updatequery['temp_newqty'][$warehouse_origin_uuid][$item_uuid])) {
                                    $newqty = $updatequery['temp_newqty'][$warehouse_origin_uuid][$item_uuid] - $item_quantity;
                                } else {
                                    $newqty = $db->getquantitydifference($item_quantity, 'item_uuid = '.$item_uuid.' AND warehouse_uuid = '.$warehouse_origin_uuid);
                                }

                                if(isset($updatequery['temp_newqty'][$warehouse_origin_uuid][$old_item_uuid])) {
                                    $newqty_2 = $updatequery['temp_newqty'][$warehouse_origin_uuid][$old_item_uuid] + $old_item_quantity;
                                } else {
                                    $newqty_2 = $db->getquantitysum($old_item_quantity, 'item_uuid = '.$old_item_uuid.' AND warehouse_uuid = '.$warehouse_origin_uuid);
                                }

                                if($newqty >= 0 AND $newqty_2 >= 0) {

                                    $updatequery['temp_newqty'][$warehouse_origin_uuid][$item_uuid] = $newqty;
                                    $updatequery['upload'][$postid][$item_uuid]['warehouse_origin'] = $warehouse_origin_uuid;
                                    $updatequery['upload'][$postid][$item_uuid]['qty'] = $newqty;
                                    $updatequery['upload'][$postid][$item_uuid]['itemuuid'] = $item_uuid;

                                    if(isset($updatequery['temp_item_data'][$warehouse_origin_uuid][$item_uuid])) {
                                        $item_data_func = $db->get_item_stock_value_parent($childtable, $item_id, $item_uuid, $warehouse_origin_uuid, $item_quantity, true, false, json_encode($updatequery['temp_item_data'][$warehouse_origin_uuid][$item_uuid]), $newqty);
                                        $updatequery['temp_item_data'][$warehouse_origin_uuid][$item_uuid] = $item_data_func;
                                        $updatequery['upload'][$postid][$item_uuid]['item_data'] = $item_data_func;
                                    } else {
                                        $item_data_func = $db->get_item_stock_value_parent($childtable, $item_id, $item_uuid, $warehouse_origin_uuid, $item_quantity, true, false);
                                        $updatequery['temp_item_data'][$warehouse_origin_uuid][$item_uuid] = $item_data_func;
                                        $updatequery['upload'][$postid][$item_uuid]['item_data'] = $item_data_func;
                                    }

                                    $updatequery['temp_newqty'][$warehouse_origin_uuid][$old_item_uuid] = $newqty_2;
                                    $updatequery['upload_old'][$postid][$old_item_uuid]['warehouse_destination'] = $warehouse_origin_uuid;
                                    $updatequery['upload_old'][$postid][$old_item_uuid]['qty'] = $newqty_2;
                                    $updatequery['upload_old'][$postid][$old_item_uuid]['itemuuid'] = $old_item_uuid;

                                    if(isset($updatequery['temp_item_data'][$warehouse_origin_uuid][$old_item_uuid])) {
                                        $item_data_func = $db->get_item_stock_value_parent($childtable, $item_id, $old_item_uuid, $warehouse_origin_uuid, $old_item_quantity, false, false, json_encode($updatequery['temp_item_data'][$warehouse_origin_uuid][$old_item_uuid]), $newqty_2);
                                        $updatequery['temp_item_data'][$warehouse_origin_uuid][$old_item_uuid] = $item_data_func;
                                        $updatequery['upload_old'][$postid][$old_item_uuid]['item_data'] = $item_data_func;
                                    } else {
                                        $item_data_func = $db->get_item_stock_value_parent($childtable, $item_id, $old_item_uuid, $warehouse_origin_uuid, $old_item_quantity, false, false);
                                        $updatequery['temp_item_data'][$warehouse_origin_uuid][$old_item_uuid] = $item_data_func;
                                        $updatequery['upload_old'][$postid][$old_item_uuid]['item_data'] = $item_data_func;
                                    }

                                } else {
                                    $updatequery = $db->displayerror($updatequery, $item_code, $item_name, $warehouse_name, $warehouse_code, 'item-'.$item_id, $postid);
                                }

                            }
                        }
                    }
                }
            }

        } else {
            // If updating Child
            $postkeyname = 'item-'.$postid;
            $getchilddata = $db->getoriginaldata($postid, '*', $childtable)[0];
            if($getchilddata['item_tempory'] == 1) {
                // $getchilddata['item_quantity'] = 0;
            }



            if(isset($outputarray[$parentid])) {
                continue;
            }
            $item_name = $getchilddata['item_name'];
            $item_code = $getchilddata['item_code'];

            $getparentdata = $db->getoriginaldata($parentid, '*', $parenttable)[0];
            $warehouse_name = $getparentdata['warehouse_origin'];
            $warehouse_code = $getparentdata['warehouse_origin_code'];

            $warehouseman_destination_approve = $getparentdata['warehouseman_destination_approve'];
            $warehouse_origin_uuid = $getparentdata['warehouse_origin_uuid'];
            $warehouse_destination_uuid = $getparentdata['warehouse_destination_uuid'];
            $calculate = 0;


            if(isset($value['item_uuid'])) {
                $old_item_uuid = $getchilddata['item_uuid'];
                $item_uuid_update = 1;
                $item_uuid = $value['item_uuid'];
                $calculate = 1;
            } else {
                $item_uuid_update = 0;
                $item_uuid = $getchilddata['item_uuid'];
            }

            if(isset($value['item_quantity'])) {
                $old_item_quantity = $getchilddata['item_quantity'];
                $item_quantity_update = 1;
                $item_quantity = $value['item_quantity'] - $old_item_quantity;
                if($item_uuid_update) {
                    $item_quantity = $value['item_quantity'];
                }
                $calculate = 1;
            } else {
                $old_item_quantity = $getchilddata['item_quantity'];
                $item_quantity_update = 0;
                $item_quantity = $getchilddata['item_quantity'];
            }


            if(isset($updatequery['deleted'][$getchilddata['id']])) {
                continue;
            }
            if($warehouseman_destination_approve AND $calculate) {
                if(isset($updatequery['temp_newqty'][$warehouse_origin_uuid][$item_uuid])) {
                    $newqty = $updatequery['temp_newqty'][$warehouse_origin_uuid][$item_uuid] - $item_quantity;
                } else {
                    $newqty = $db->getquantitydifference($item_quantity, 'item_uuid = '.$item_uuid.' AND warehouse_uuid = '.$warehouse_origin_uuid);
                }
                if(isset($updatequery['temp_newqty'][$warehouse_destination_uuid][$item_uuid])) {
                    $newqty_destination = $updatequery['temp_newqty'][$warehouse_destination_uuid][$item_uuid] + $item_quantity;
                } else {
                    $newqty_destination = $db->getquantitysum($item_quantity, 'item_uuid = '.$item_uuid.' AND warehouse_uuid = '.$warehouse_destination_uuid);
                }
                if($newqty >= 0 AND $newqty_destination >= 0) {
                    if($item_uuid_update) {
                        if(isset($updatequery['temp_newqty'][$warehouse_origin_uuid][$old_item_uuid])) {
                            $newqtyinner = $updatequery['temp_newqty'][$warehouse_origin_uuid][$old_item_uuid] + $old_item_quantity;
                        } else {
                            $newqtyinner = $db->getquantitysum($old_item_quantity, 'item_uuid = '.$old_item_uuid.' AND warehouse_uuid = '.$warehouse_origin_uuid);
                        }
                        if(isset($updatequery['temp_newqty'][$warehouse_destination_uuid][$old_item_uuid])) {
                            $newqtyinner_destination = $updatequery['temp_newqty'][$warehouse_destination_uuid][$old_item_uuid] - $old_item_quantity;
                        } else {
                            $newqtyinner_destination = $db->getquantitydifference($old_item_quantity, 'item_uuid = '.$old_item_uuid.' AND warehouse_uuid = '.$warehouse_destination_uuid);
                        }
                        // FORM HERE
                        if($newqtyinner >= 0 AND $newqtyinner_destination >= 0) {
                            $updatequery['temp_newqty'][$warehouse_origin_uuid][$item_uuid] = $newqty;
                            $updatequery['nocolorid'][] = 'item-'.$postid;

                            $updatequery['upload'][$parentid][$item_uuid]['warehouse_origin'] = $warehouse_origin_uuid;
                            $updatequery['upload'][$parentid][$item_uuid]['qty'] = $newqty;
                            $updatequery['upload'][$parentid][$item_uuid]['itemuuid'] = $item_uuid;

                            if(isset($updatequery['temp_item_data'][$warehouse_origin_uuid][$item_uuid])) {
                                $item_data_func = $db->get_item_stock_value_child_complete_qty($childtable, $postid, $item_uuid, $warehouse_origin_uuid, $item_quantity, true, false, json_encode($updatequery['temp_item_data'][$warehouse_origin_uuid][$item_uuid]), $newqty);
                                $updatequery['temp_item_data'][$warehouse_origin_uuid][$item_uuid] = $item_data_func;
                                $updatequery['upload'][$parentid][$item_uuid]['item_data'] = $item_data_func;
                            } else {
                                $item_data_func = $db->get_item_stock_value_child_complete_qty($childtable, $postid, $item_uuid, $warehouse_origin_uuid, $item_quantity, true, false);
                                $updatequery['temp_item_data'][$warehouse_origin_uuid][$item_uuid] = $item_data_func;
                                $updatequery['upload'][$parentid][$item_uuid]['item_data'] = $item_data_func;
                            }

                            $updatequery['temp_newqty'][$warehouse_origin_uuid][$old_item_uuid] = $newqtyinner;
                            $updatequery['upload_old'][$parentid][$item_uuid]['warehouse_origin'] = $warehouse_origin_uuid;
                            $updatequery['upload_old'][$parentid][$item_uuid]['qty'] = $newqtyinner;
                            $updatequery['upload_old'][$parentid][$item_uuid]['itemuuid'] = $old_item_uuid;

                            if(isset($updatequery['temp_item_data'][$warehouse_origin_uuid][$item_uuid])) {
                                $item_data_func = $db->get_item_stock_value_child_complete_qty($childtable, $postid, $old_item_uuid, $warehouse_origin_uuid, $old_item_quantity, false, false, json_encode($updatequery['temp_item_data'][$warehouse_origin_uuid][$item_uuid]), $newqtyinner);
                                $updatequery['temp_item_data'][$warehouse_origin_uuid][$item_uuid] = $item_data_func;
                                $updatequery['upload_old'][$parentid][$item_uuid]['item_data'] = $item_data_func;
                            } else {
                                $item_data_func = $db->get_item_stock_value_child_complete_qty($childtable, $postid, $old_item_uuid, $warehouse_origin_uuid, $old_item_quantity, false, false);
                                $updatequery['temp_item_data'][$warehouse_origin_uuid][$item_uuid] = $item_data_func;
                                $updatequery['upload_old'][$parentid][$item_uuid]['item_data'] = $item_data_func;
                            }

                            $updatequery['temp_newqty'][$warehouse_destination_uuid][$item_uuid] = $newqty_destination;
                            $updatequery['nocolorid'][] = 'item-'.$postid;
                            $updatequery['upload_transfer'][$parentid][$item_uuid]['warehouse_destination'] = $warehouse_destination_uuid;
                            $updatequery['upload_transfer'][$parentid][$item_uuid]['qty'] = $newqty_destination;
                            $updatequery['upload_transfer'][$parentid][$item_uuid]['itemuuid'] = $item_uuid;

                            if(isset($updatequery['temp_item_data'][$warehouse_destination_uuid][$item_uuid])) {
                                $item_data_func = $db->get_item_stock_value_child_complete_qty($childtable, $postid, $item_uuid, $warehouse_destination_uuid, $item_quantity, false, false, json_encode($updatequery['temp_item_data'][$warehouse_destination_uuid][$item_uuid]), $newqty_destination);
                                $updatequery['temp_item_data'][$warehouse_destination_uuid][$item_uuid] = $item_data_func;
                                $updatequery['upload_transfer'][$parentid][$item_uuid]['item_data'] = $item_data_func;
                            } else {
                                $item_data_func = $db->get_item_stock_value_child_complete_qty($childtable, $postid, $item_uuid, $warehouse_destination_uuid, $item_quantity, false, false);
                                $updatequery['temp_item_data'][$warehouse_destination_uuid][$item_uuid] = $item_data_func;
                                $updatequery['upload_transfer'][$parentid][$item_uuid]['item_data'] = $item_data_func;
                            }

                            $updatequery['temp_newqty'][$warehouse_destination_uuid][$old_item_uuid] = $newqtyinner_destination;
                            $updatequery['upload_old_transfer'][$parentid][$item_uuid]['warehouse_destination'] = $warehouse_destination_uuid;
                            $updatequery['upload_old_transfer'][$parentid][$item_uuid]['qty'] = $newqtyinner_destination;
                            $updatequery['upload_old_transfer'][$parentid][$item_uuid]['itemuuid'] = $old_item_uuid;

                            if(isset($updatequery['temp_item_data'][$warehouse_destination_uuid][$item_uuid])) {
                                $item_data_func = $db->get_item_stock_value_child_complete_qty($childtable, $postid, $old_item_uuid, $warehouse_destination_uuid, $old_item_quantity, true, false, json_encode($updatequery['temp_item_data'][$warehouse_destination_uuid][$item_uuid]), $newqtyinner_destination);
                                $updatequery['temp_item_data'][$warehouse_destination_uuid][$item_uuid] = $item_data_func;
                                $updatequery['upload_old_transfer'][$parentid][$item_uuid]['item_data'] = $item_data_func;
                            } else {
                                $item_data_func = $db->get_item_stock_value_child_complete_qty($childtable, $postid, $old_item_uuid, $warehouse_destination_uuid, $old_item_quantity, true, false);
                                $updatequery['temp_item_data'][$warehouse_destination_uuid][$item_uuid] = $item_data_func;
                                $updatequery['upload_old_transfer'][$parentid][$item_uuid]['item_data'] = $item_data_func;
                            }

                        } else {
                            $updatequery = $db->displayerror($updatequery, $item_code, $item_name, $warehouse_name, $warehouse_code, 'item-'.$postid, $parentid);
                        }
                    } else {
                        $updatequery['temp_newqty'][$warehouse_origin_uuid][$item_uuid] = $newqty;
                        $updatequery['nocolorid'][] = 'item-'.$postid;
                        $updatequery['upload'][$parentid][$item_uuid]['warehouse_origin'] = $warehouse_origin_uuid;
                        $updatequery['upload'][$parentid][$item_uuid]['qty'] = $newqty;
                        $updatequery['upload'][$parentid][$item_uuid]['itemuuid'] = $item_uuid;

                        if(isset($updatequery['temp_item_data'][$warehouse_origin_uuid][$item_uuid])) {
                            $item_data_func = $db->get_item_stock_value_child_incomplete_qty($childtable, $postid, $item_uuid, $warehouse_origin_uuid, $item_quantity, true, true, json_encode($updatequery['temp_item_data'][$warehouse_origin_uuid][$item_uuid]), $newqty);
                            $updatequery['temp_item_data'][$warehouse_origin_uuid][$item_uuid] = $item_data_func;
                            $updatequery['upload'][$parentid][$item_uuid]['item_data'] = $item_data_func;
                        } else {
                            $item_data_func = $db->get_item_stock_value_child_incomplete_qty($childtable, $postid, $item_uuid, $warehouse_origin_uuid, $item_quantity, true, true);
                            $updatequery['temp_item_data'][$warehouse_origin_uuid][$item_uuid] = $item_data_func;
                            $updatequery['upload'][$parentid][$item_uuid]['item_data'] = $item_data_func;
                        }

                        $updatequery['temp_newqty'][$warehouse_destination_uuid][$item_uuid] = $newqty_destination;
                        $updatequery['nocolorid'][] = 'item-'.$postid;

                        $updatequery['upload_transfer'][$parentid][$item_uuid]['warehouse_destination'] = $warehouse_destination_uuid;
                        $updatequery['upload_transfer'][$parentid][$item_uuid]['qty'] = $newqty_destination;
                        $updatequery['upload_transfer'][$parentid][$item_uuid]['itemuuid'] = $item_uuid;

                        if(isset($updatequery['temp_item_data'][$warehouse_destination_uuid][$item_uuid])) {
                            $item_data_func = $db->get_item_stock_value_child_incomplete_qty($childtable, $postid, $item_uuid, $warehouse_destination_uuid, $item_quantity, false, true, json_encode($updatequery['temp_item_data'][$warehouse_destination_uuid][$item_uuid]), $newqty_destination);
                            $updatequery['temp_item_data'][$warehouse_destination_uuid][$item_uuid] = $item_data_func;
                            $updatequery['upload_transfer'][$parentid][$item_uuid]['item_data'] = $item_data_func;
                        } else {
                            $item_data_func = $db->get_item_stock_value_child_incomplete_qty($childtable, $postid, $item_uuid, $warehouse_destination_uuid, $item_quantity, false, true);
                            $updatequery['temp_item_data'][$warehouse_destination_uuid][$item_uuid] = $item_data_func;
                            $updatequery['upload_transfer'][$parentid][$item_uuid]['item_data'] = $item_data_func;
                        }

                    }
                } else {
                    $updatequery = $db->displayerror($updatequery, $item_code, $item_name, $warehouse_name, $warehouse_code, 'item-'.$postid, $parentid);
                }
            }
        }
    }

    foreach ($AI as $I)
    {

        $A = $SXML ? $I->attributes() : $Xml->attributes[$I];
        // --- end of simple xml or php xml ---
        if (!empty($A["Added"]))
        {
            // warehouse
            // warehouse_origin_uuid
            // warehouse_code
            if ($A["Parent"] == 0)
            {
                $parentid = (int)$A["id"];
                $tempparentid = (int)$A["id"];
                $A['document_no'] = $db->generatedocumentno($parenttable, (string)$A['document_no'], (string)$A['document_abbrevation']);

                do
                {
                    $addparent = $db->Exec("INSERT INTO ".$parenttable."(`id`, `grid_id`, `Parent`, `Def`, `note`, `uuid`, `quantity`, `document_type`, `document_abbrevation`, `warehouse_origin`, `warehouse_origin_code`, `posting_date`, `document_no`, `document_date`, `warehouseman`, `warehouse_origin_uuid`, `warehouseman_department`, `warehouse_destination_uuid`, `warehouse_destination`, `warehouse_destination_code`, `warehouseman_destination`, `warehouseman_destination_department`, `warehouseman_destination_approve`, `has_child`) VALUES('". $parentid ."','". $parentid ."','". $A["Parent"] ."','". $A["Def"] ."','". $A["note"] ."','". $A["uuid"] ."','". $A["item_quantity"] ."','". $A["document_type"] ."','". $A["document_abbrevation"] ."','". $A["warehouse_origin"] ."','". $A["warehouse_origin_code"] ."','". $A["posting_date"] ."','". $A["document_no"] ."','". $A["document_date"] ."','". $A["warehouseman"] ."','". $A["warehouse_origin_uuid"] ."','". $A["warehouseman_department"] ."','". $A["warehouse_destination_uuid"] ."','". $A["warehouse_destination"] ."','". $A["warehouse_destination_code"] ."','". $A["warehouseman_destination"] ."','". $A["warehouseman_destination_department"] ."','". $A["warehouseman_destination_approve"] ."','')");

                    if ($addparent != '-1')
                    {
                    }
                    else
                    {
                        $parentid = $parentid + 1;
                    }
                }
                while ($addparent == '-1');
                $lastinsertid = $db->getlastInsertId();
                $newaddedrow[$tempparentid] = $lastinsertid;
                $newaddedrowvalueoldid[$tempparentid] = (int)$A["id"];

                $parentmainid = (string)$A["id"];
                $getparentid[$parentmainid] = $parentmainid;

                $getoriginuuid[$lastinsertid]['id'] = (string) $A["warehouse_origin_uuid"];
                $getoriginuuid[$lastinsertid]['destination_uuid'] = (string) $A["warehouse_destination_uuid"];
                $lastinsertidarray[$parentmainid] = $lastinsertid;
                $getdestinationapprove[$lastinsertid] = (string)$A['warehouseman_destination_approve'];
                $getwarehousenamearray[$lastinsertid] = (string)$A['warehouse_origin'];
                $getwarehousecodearray[$lastinsertid] = (string)$A['warehouse_origin_code'];
            }
            else
            {

                $parentid = (int)$A["Parent"];
                if (!isset($blockparent[$parentid]))
                {
                    $getallchild = $db->getoriginalparentdata($parentid, 'id, item_tempory', $childtable);
                    foreach ($getallchild as $getallchildkey => $getallchildvalue) {
                        if($getallchildvalue['item_tempory']) {
                            $blockparent[$parentid] = $getallchildvalue['id'];
                        }
                    }
                }

                $displayparentid = '';
                // item_note
                if (!isset($getparentid[$parentid]))
                {
                    $getparentrows = "SELECT warehouse_origin_uuid, warehouse_destination_uuid, warehouseman_destination_approve FROM ".$parenttable." WHERE id = '" . $parentid . "'";
                    $getparentrows = $db->Query($getparentrows);
                    $getparentrows = $getparentrows->GetRows();

                    if(isset($outputarray[$parentid]['warehouse_destination_uuid'])) {
                        $destination_uuid = $outputarray[$parentid]['warehouse_destination_uuid'];
                    } else {
                        $destination_uuid = (string)$getparentrows[0]['warehouse_destination_uuid'];
                    }
                    if(isset($outputarray[$parentid]['warehouse_origin_uuid'])) {
                        $origin = $outputarray[$parentid]['warehouse_origin_uuid'];
                    } else {
                        $origin = (string)$getparentrows[0]['warehouse_origin_uuid'];
                    }
                    if(isset($outputarray[$parentid]['warehouseman_destination_approve'])) {
                        $approve = $outputarray[$parentid]['warehouseman_destination_approve'];
                    } else {
                        $approve = (string)$getparentrows[0]['warehouseman_destination_approve'];
                    }

                    if(isset($outputarray[$parentid]['warehouse_origin'])) {
                        $getwarehousename = $outputarray[$parentid]['warehouse_origin'];
                    } else {
                        $getwarehousename = $db->getwarehousename($parenttable, $parentid, 'id', 'warehouse_origin');
                    }
                    if(isset($outputarray[$parentid]['warehouse_origin_code'])) {
                        $getwarehousecode = $outputarray[$parentid]['warehouse_origin_code'];
                    } else {
                        $getwarehousecode = $db->getwarehousename($parenttable, $parentid, 'id', 'warehouse_origin_code');
                    }

                    $displayparentid = (int)$parentid;
                }
                else
                {
                    $displayparentid = $lastinsertidarray[$parentid];
                    $destination_uuid = (string)$getoriginuuid[$displayparentid]['destination_uuid'];
                    $origin = (string)$getoriginuuid[$displayparentid]['id'];
                    $approve = (string)$getdestinationapprove[$displayparentid];
                    $getwarehousename = $getwarehousenamearray[$displayparentid];
                    $getwarehousecode = $getwarehousecodearray[$displayparentid];
                }

                $itemuuid = (string)$A["item_uuid"];
                $itemquantity = (float)$A["item_quantity"];
                $tempory = 0;

                $childid = str_replace("item-", "", (string)$A['id']);
                $tempchildid = str_replace("item-", "", (string)$A['id']);
                $tempparentid = (int)$A["id"];
                do
                {
                    $addparent = $db->Exec("INSERT INTO ".$childtable."(`id`, `grid_id`, `Parent`, `Def`, `item_note`, `item_name`, `item_code`, `item_barcode`, `item_brand`, `item_category`, `item_subcategory`, `item_unit`, `item_type`, `item_description`, `item_quantity`, `item_uuid`, `item_tempory`) VALUES('". $childid ."','". $childid ."','". $displayparentid ."','". $A["Def"] ."','". $A["note"] ."','". $A["item_name"] ."','". $A["item_code"] ."','". $A["item_barcode"] ."','". $A["item_brand"] ."','". $A["item_category"] ."','". $A["item_subcategory"] ."','". $A["item_unit"] ."','". $A["item_type"] ."','','". $A["item_quantity"] ."','". $A["item_uuid"] ."', '".$tempory."')");
                    if ($addparent != '-1')
                    {
                    }
                    else
                    {
                        $childid = $childid + 1;
                    }
                }
                while ($addparent == '-1');
                $A['Parent'] = $displayparentid;
                $lastinsertedidchild = $db->getlastInsertId();
                $newaddedrowchild[$tempparentid] = $lastinsertedidchild;

                if ($approve == 1)
                {

                    if(isset($updatequery['temp_newqty'][$origin][$itemuuid])) {
                        $newqty = $updatequery['temp_newqty'][$origin][$itemuuid] - $itemquantity;
                    } else {
                        $newqty = $db->getquantitydifference($itemquantity, 'item_uuid = '.$itemuuid.' AND warehouse_uuid = '.$origin);
                    }

                    if($newqty >= 0) {
                        // form here 2
                        $updatequery['temp_newqty'][$origin][$itemuuid] = $newqty;
                        $updatequery['upload'][$displayparentid][$itemuuid]['warehouse_origin'] = $origin;
                        $updatequery['upload'][$displayparentid][$itemuuid]['qty'] = $newqty;
                        $updatequery['upload'][$displayparentid][$itemuuid]['itemuuid'] = $itemuuid;

                        if(isset($updatequery['temp_item_data'][$origin][$itemuuid])) {
                            $item_data_func = $db->get_item_stock_value_insert($childtable, $lastinsertedidchild, $itemuuid, $origin, $itemquantity, true, json_encode($updatequery['temp_item_data'][$origin][$itemuuid]), $newqty);
                            $updatequery['temp_item_data'][$origin][$itemuuid] = $item_data_func;
                            $updatequery['upload'][$displayparentid][$itemuuid]['item_data'] = $item_data_func;
                        } else {
                            $item_data_func = $db->get_item_stock_value_insert($childtable, $lastinsertedidchild, $itemuuid, $origin, $itemquantity, true);
                            $updatequery['temp_item_data'][$origin][$itemuuid] = $item_data_func;
                            $updatequery['upload'][$displayparentid][$itemuuid]['item_data'] = $item_data_func;
                        }


                        if(isset($updatequery['temp_newqty'][$destination_uuid][$itemuuid])) {
                            $destination_newqty = $updatequery['temp_newqty'][$destination_uuid][$itemuuid] + $itemquantity;
                        } else {
                            $destination_newqty = $db->getquantitysum($itemquantity, 'item_uuid = '.$itemuuid.' AND warehouse_uuid = '.$destination_uuid);
                        }

                        if($destination_newqty >= 0) {
                            $updatequery['temp_newqty'][$destination_uuid][$itemuuid] = $destination_newqty;
                            $updatequery['upload_old'][$displayparentid][$itemuuid]['warehouse_destination'] = $destination_uuid;
                            $updatequery['upload_old'][$displayparentid][$itemuuid]['qty'] = $destination_newqty;
                            $updatequery['upload_old'][$displayparentid][$itemuuid]['itemuuid'] = $itemuuid;

                            if(isset($updatequery['temp_item_data'][$destination_uuid][$itemuuid])) {
                                $item_data_func = $db->get_item_stock_value_insert($childtable, $lastinsertedidchild, $itemuuid, $destination_uuid, $itemquantity, false, json_encode($updatequery['temp_item_data'][$destination_uuid][$itemuuid]), $destination_newqty);
                                $updatequery['temp_item_data'][$destination_uuid][$itemuuid] = $item_data_func;
                                $updatequery['upload_old'][$displayparentid][$itemuuid]['item_data'] = $item_data_func;
                            } else {
                                $item_data_func = $db->get_item_stock_value_insert($childtable, $lastinsertedidchild, $itemuuid, $destination_uuid, $itemquantity, false);
                                $updatequery['temp_item_data'][$destination_uuid][$itemuuid] = $item_data_func;
                                $updatequery['upload_old'][$displayparentid][$itemuuid]['item_data'] = $item_data_func;
                            }

                        } else {
                            $itemidtodis = $lastinsertedidchild;
                            $getitemname = (string)$A['item_name'];
                            $getitemcode = (string)$A['item_code'];

                            $updatequery = $db->displayerroroninsert($updatequery, $getitemcode, $getitemname, $getwarehousename, $getwarehousecode, $displayparentid);
                            $tempory = 1;
                            $S = "UPDATE ".$parenttable." SET `warehouseman_destination_approve` = 0 WHERE `grid_id` = '" . $displayparentid . "'";
                            $db->Exec($S);
                        }

                    } else {
                        $itemidtodis = (string)$A['id'];
                        $getitemname = (string)$A['item_name'];
                        $getitemcode = (string)$A['item_code'];

                        $updatequery = $db->displayerroroninsert($updatequery, $getitemcode, $getitemname, $getwarehousename, $getwarehousecode, $displayparentid);
                        $tempory = 1;
                        $S = "UPDATE ".$parenttable." SET `warehouseman_destination_approve` = 0 WHERE `grid_id` = '" . $displayparentid . "'";
                        $db->Exec($S);
                    }
                    $updatequery['updatequery'][$displayparentid][] = '';
                }


                $S = "UPDATE ".$parenttable." SET `has_child` = 1 WHERE `grid_id` = '" . $displayparentid . "'";
                $db->Exec($S);
            }

        }
    }
    // echo "<pre>";
    // print_r($updatequery);
    // echo "</pre>";

    // exit();
    if (isset($updatequery['updatequery']))
    {
        foreach ($updatequery['updatequery'] as $key => $value)
        {
            if(!isset($updatequery['break'][$key]))
            {
                if(isset($updatequery['upload_old'][$key])) {
                    foreach ($updatequery['upload_old'][$key] as $innkey => $invalue)
                    {
                        $itemuuid = $updatequery['upload_old'][$key][$innkey]['itemuuid'];
                        $newqty = $updatequery['upload_old'][$key][$innkey]['qty'];
                        if(isset($updatequery['upload_old'][$key][$innkey]['warehouse_origin'])) {
                            $warehouse = $updatequery['upload_old'][$key][$innkey]['warehouse_origin'];
                        } else {
                            $warehouse = $updatequery['upload_old'][$key][$innkey]['warehouse_destination'];
                        }

                        $item_data = $updatequery['upload_old'][$key][$innkey]['item_data'];
                        if(isset($item_data['item_cost'])) {
                            $db->update_item_cost($item_data['item_cost'], $childtable);
                            $db->update_item_cost_quantity($item_data['item_cost_quantity'], $childtable);
                            $db->update_item_stock_value($item_data['item_stock_value'], $itemuuid, $warehouse);
                        }

                        $db->updatewarehouseqty_entry($newqty, $itemuuid, $warehouse);
                    }
                }
                if(isset($updatequery['upload'][$key])) {
                    foreach ($updatequery['upload'][$key] as $innkey => $invalue)
                    {
                        $itemuuid = $updatequery['upload'][$key][$innkey]['itemuuid'];
                        $newqty = $updatequery['upload'][$key][$innkey]['qty'];
                        if(isset($updatequery['upload'][$key][$innkey]['warehouse_origin'])) {
                            $warehouse = $updatequery['upload'][$key][$innkey]['warehouse_origin'];
                        } else {
                            $warehouse = $updatequery['upload'][$key][$innkey]['warehouse_destination'];
                        }

                        $item_data = $updatequery['upload'][$key][$innkey]['item_data'];
                        if(isset($item_data['item_cost'])) {
                            $db->update_item_cost($item_data['item_cost'], $childtable);
                            $db->update_item_cost_quantity($item_data['item_cost_quantity'], $childtable);
                            $db->update_item_stock_value($item_data['item_stock_value'], $itemuuid, $warehouse);
                        }

                        $db->updatewarehouseqty_entry($newqty, $itemuuid, $warehouse);
                    }
                }
                if(isset($updatequery['upload_old_transfer'][$key])) {
                    foreach ($updatequery['upload_old_transfer'][$key] as $innkey => $invalue)
                    {
                        $itemuuid = $updatequery['upload_old_transfer'][$key][$innkey]['itemuuid'];
                        $newqty = $updatequery['upload_old_transfer'][$key][$innkey]['qty'];
                        if(isset($updatequery['upload_old_transfer'][$key][$innkey]['warehouse_origin'])) {
                            $warehouse = $updatequery['upload_old_transfer'][$key][$innkey]['warehouse_origin'];
                        } else {
                            $warehouse = $updatequery['upload_old_transfer'][$key][$innkey]['warehouse_destination'];
                        }

                        $item_data = $updatequery['upload_old_transfer'][$key][$innkey]['item_data'];
                        if(isset($item_data['item_cost'])) {
                            $db->update_item_cost($item_data['item_cost'], $childtable);
                            $db->update_item_cost_quantity($item_data['item_cost_quantity'], $childtable);
                            $db->update_item_stock_value($item_data['item_stock_value'], $itemuuid, $warehouse);
                        }

                        $db->updatewarehouseqty_entry($newqty, $itemuuid, $warehouse);
                    }
                }
                if(isset($updatequery['upload_transfer'][$key])) {
                    foreach ($updatequery['upload_transfer'][$key] as $innkey => $invalue)
                    {
                        $itemuuid = $updatequery['upload_transfer'][$key][$innkey]['itemuuid'];
                        $newqty = $updatequery['upload_transfer'][$key][$innkey]['qty'];
                        if(isset($updatequery['upload_transfer'][$key][$innkey]['warehouse_origin'])) {
                            $warehouse = $updatequery['upload_transfer'][$key][$innkey]['warehouse_origin'];
                            $transfer = '';
                        } else {
                            $warehouse = $updatequery['upload_transfer'][$key][$innkey]['warehouse_destination'];
                            $transfer = true;
                        }

                        $item_data = $updatequery['upload_transfer'][$key][$innkey]['item_data'];
                        if(isset($item_data['item_cost'])) {
                            $db->update_item_cost($item_data['item_cost'], $childtable);
                            $db->update_item_cost_quantity($item_data['item_cost_quantity'], $childtable);
                            $db->update_item_stock_value($item_data['item_stock_value'], $itemuuid, $warehouse);
                        }

                        $db->updatewarehouseqty_entry($newqty, $itemuuid, $warehouse);
                    }
                }
                // updatewarehouseqty
                foreach ($value as $innkey => $innvalue)
                {
                    $db->Exec($innvalue);
                }
            } else {
                if(isset($updatequery['upload'][$key]))
                {
                    foreach ($updatequery['upload'][$key] as $innkey => $innvalue) {
                        $db->Exec($updatequery['updatequery'][$key][$innkey]);
                    }
                }
            }
        }
    }
    // print_r($updatequery);
    // exit();


    // if (!isset($deletearray['deleteblock']))
    // {
    //     $deletearray['deleteblock'] = array();
    // }
    // if (!isset($deletearray['item']))
    // {
    //     $deletearray['item'] = array();
    // }
    // if (isset($deletearray['parent']))
    // {
    //     if (count($deletearray['parent']) > 0)
    //     {
    //         foreach ($deletearray['parent'] as $deletekey => $deletevalue)
    //         {
    //             if (!isset($deletearray['deleteblock'][$deletevalue]))
    //             {
    //                 $db->delete("DELETE FROM ".$parenttable." WHERE id='" . $deletevalue . "'");
    //                 $deletearray['deleted'][$deletevalue] = '';
    //             }
    //         }
    //     }
    // }
    // if (isset($deletearray['item']))
    // {
    //     if (count($deletearray['item']) > 0)
    //     {
    //         foreach ($deletearray['item'] as $deletekey => $deletevalue)
    //         {
    //             $deleteparentid = $deletearray['itemparent'][$deletekey];
    //             if (!isset($deletearray['deleteblock'][$deleteparentid]))
    //             {
    //                 $db->delete("DELETE FROM ".$childtable." WHERE id='" . $deletevalue . "'");
    //                 $deletearray['deleted']['item-'.$deletevalue] = '';
    //             }
    //             else
    //             {
    //                 if (!empty($deletearray['itemuuid'][$deleteparentid]))
    //                 {
    //                     $db->updateorcreatewarehouse($deletearray['itemuuid'][$deleteparentid], $deletearray['origin'][$deleteparentid], $deletearray['itemquantity'][$deleteparentid]);
    //                 }
    //             }
    //         }
    //     }
    // }

    $changeparentdisnewaddedrow = '';
    $newsetsession = array();
    $addedd = '';
    $getallkeyid = $db->getall($parenttable);
    foreach ($getallkeyid as $gtallkeykey => $gtallkeyvalue) {
        $changefields = '';
        $errorpostid = $gtallkeyvalue['id'];
        $tabledata = $db->getparentdetail($errorpostid, $parenttable);
        foreach ($tabledata[0] as $tablekey => $tablevalue) {
            if($tablekey != 'id') {
                $changefields .= $tablekey.'=\''.$tablevalue.'\' ';
            }
        }
        if(isset($updatequery['deletebreak'])) {
            if(isset($updatequery['deletebreak'][$errorpostid])) {
                $addedd .= '<I Added=\'1\' Color=\'rgb(255, 229, 228)\' id=\''.$errorpostid.'\' '.$changefields.'/>';
            }
        }
        $color = '';
        if(count($updatequery['nocolorid']) > 0) {
            if(isset($updatequery['nocolorid'][$gtallkeyvalue['id']])) {
                $color = 'Color=\'\'';
            }
        }
        $VARnewId = 'id=\''.$gtallkeyvalue['id'] . '\'';
        if (count($newaddedrow) > 0)
        {
            foreach ($newaddedrow as $key => $value)
            {
                if($gtallkeyvalue['id'] == $value) {
                    $VARnewId = 'id=\''.$key . '\' NewId=\''.$value.'\'';
                }
            }
        }

        $dischanges .= '<I '.$VARnewId.' Changed=\'1\' '.$color.' '.$changefields.'/>';
    }
    $getallkeyid = $db->getall($childtable);
    foreach ($getallkeyid as $gtallkeykey => $gtallkeyvalue) {


        $color = 'Color=\'\'';
        if($gtallkeyvalue['item_tempory'] == 1) {
            $color = 'Color=\'rgb(255, 229, 228)\'';
        }
        $changefields = '';
        $errorpostid = $gtallkeyvalue['id'];
        $tabledata = $db->getparentdetail($errorpostid, $childtable);
        foreach ($tabledata[0] as $tablekey => $tablevalue) {
            if($tablekey != 'id') {
                $changefields .= $tablekey.'=\''.$tablevalue.'\' ';
            }
        }
        if(isset($updatequery['deletebreak'])) {
            if(isset($updatequery['deletebreak'][$tabledata[0]['Parent']])) {
                $addedd .= '<I Added=\'1\' Color=\'rgb(255, 229, 228)\' id=\'item-'.$errorpostid.'\' '.$changefields.'/>';
            }
        }
        if(count($updatequery['nocolorid']) > 0) {
            if(isset($updatequery['nocolorid'][$gtallkeyvalue['id']])) {
                $color = 'Color=\'\'';
            }
        }

        $VARnewId = 'id=\'item-'.$gtallkeyvalue['id'] . '\'';
        if (count($newaddedrowchild) > 0)
        {
            foreach ($newaddedrowchild as $key => $value)
            {
                if($gtallkeyvalue['id'] == $value) {
                    $VARnewId = 'id=\''.$key . '\' NewId=\'item-'.$value.'\'';
                }
            }
        }

        if (isset($updatequery['givenitemid']['item-'.$gtallkeyvalue['id']]))
        {
            $color = 'Color=\'rgb(255, 229, 228)\'';
        }

        $dischanges .= '<I '.$VARnewId.' Changed=\'1\' '.$color.' '.$changefields.'/>';
    }
    if (isset($updatequery['error']))
    {
        foreach ($updatequery['error'] as $key => $value)
        {
            $diserror .= $value . '
';
        }

        $output = '<Grid><IO Result="0" Message="' . $diserror . '"/>
            <Changes>' . $dischanges .'</Changes>
            <Changes Added="1">' . $addedd .'</Changes>';
        $output .= '</Grid>';
    }
    else
    {
        $output = '<Grid><IO Result="0" Message="' . $diserror . '"/>
            <Changes>' . $dischanges . '</Changes>
            <Changes Added="1">' . $addedd .'</Changes>';
        $output .= '</Grid>';

    }
    $output = htmlspecialchars($output, ENT_COMPAT);
    echo $output;

}
else
{

    $rs = $db->Query("SELECT * FROM ".$parenttable." ORDER BY id");
    $rows = $rs->GetRows();
    if ($rows != NULL)
    {
        $XML = "<Grid><Body><B>";
        foreach ($rows as $row)
        {
            $XML .= "<I Level='0' Def='".$row["Def"]."' id='" . $row["id"] . "'"
                . " grid_id='" . $row["grid_id"] . "'"
                . " document_type='" . $row["document_type"] . "'"
                . " document_abbrevation='" . $row["document_abbrevation"] . "'"
                . " document_no='" . $row["document_no"] . "'"
                . " posting_date='" . $row["posting_date"] . "'"
                . " document_date='" . $row["document_date"] . "'"
                . " document_date='".$row['document_date']  . "'"
                . " warehouse_origin='".$row['warehouse_origin']  . "'"
                . " warehouse_origin_code='".$row['warehouse_origin_code']  . "'"
                . " warehouse_destination='".$row['warehouse_destination']  . "'"
                . " warehouse_destination_uuid='".$row['warehouse_destination_uuid']  . "'"
                . " item_name=''"
                . " item_code=''"
                . " item_type=''"
                . " item_barcode=''"
                . " item_brand=''"
                . " item_subcategory=''"
                . " item_category=''"
                . " item_unit=''"
                . " item_cost=''"
                . " item_price=''"
                . " item_quantity=''"
                . " debit_quantity=''"
                . " credit_quantity=''"
                . " warehouseman='".$row['warehouseman']  . "'"
                . " warehouseman_department='".$row['warehouseman_department']  . "'"
                . " warehouseman_destination_approve='".$row['warehouseman_destination_approve']  . "'"
                . " deliveryman=''"
                . " deliveryman_department=''"
                . " deliveryman_approve=''"
                . " warehouseman_destination='".$row['warehouseman_destination']  . "'"
                . " warehouseman_destination_department='".$row['warehouseman_destination_department']  . "'"
                . " warehouseman_destination_approve='".$row['warehouseman_destination_approve']  . "'"
                . " note='".$row['note']  . "'"
                . " status=''"
                . " item_uuid=''"
                . " warehouse_origin_uuid='" . $row["warehouse_origin_uuid"] . "'"
                . " warehouse_destination_uuid='" . $row["warehouse_destination_uuid"] . "'"
                . " uuid='" . $row["uuid"] . "'"
            ."/>";

            if ($row['has_child'])
            {
                $rschild = $db->Query("SELECT * FROM ".$childtable." WHERE Parent = '" . $row["grid_id"] . "' ORDER BY id");
                $rowschild = $rschild->GetRows();
                if ($rowschild != NULL)
                {
                    foreach ($rowschild as $rowchild)
                    {
                      $XML .= "<I Level='1' Def='".$rowchild["Def"]."' id='item-" . $rowchild["id"] . "'"
                          . " grid_id='item-" . $rowchild["grid_id"] . "'"
                          . " document_type=''"
                          . " document_abbrevation=''"
                          . " document_no=''"
                          . " posting_date=''"
                          . " document_date=''"
                          . " warehouse_origin=''"
                          . " warehouse_origin_code=''"
                          . " warehouse_destination=''"
                          . " warehouse_destination_uuid=''"
                          . " item_name='" . $rowchild["item_name"] . "'"
                          . " item_code='" . $rowchild["item_code"] . "'"
                          . " item_type='" . $rowchild["item_type"] . "'"
                          . " item_barcode='" . $rowchild["item_barcode"] . "'"
                          . " item_brand='" . $rowchild["item_brand"] . "'"
                          . " item_subcategory='" . $rowchild["item_subcategory"] . "'"
                          . " item_category='" . $rowchild["item_category"] . "'"
                          . " item_unit='" . $rowchild["item_unit"] . "'"
                          . " item_cost=''"
                          . " item_price=''"
                          . " item_quantity='" . $rowchild["item_quantity"] . "'"
                          . " debit_quantity=''"
                          . " credit_quantity=''"
                          . " warehouseman=''"
                          . " warehouseman_department=''"
                          . " warehouseman_destination_approve=''"
                          . " deliveryman=''"
                          . " deliveryman_department=''"
                          . " deliveryman_approve=''"
                          . " warehouseman_destination=''"
                          . " warehouseman_destination_department=''"
                          . " warehouseman_destination_approve=''"
                          . " note='".$rowchild['item_note']  . "'"
                          . " status=''"
                          . " item_uuid='" . $rowchild["item_uuid"] . "'"
                          . " warehouse_destination_uuid=''"; if($rowchild["item_tempory"] == '1') { $XML .= " Color='rgb(255, 229, 228)'"; } $XML .= " uuid=''"
                      ."/>";
                    }
                }
            }
        }
        $XML .= "</B></Body></Grid>";
        $XML = htmlspecialchars($XML, ENT_COMPAT);
    }
    else
    {
        $XML = "<Grid><Body><B>";
        $XML .= "</B></Body></Grid>";
        $XML = htmlspecialchars($XML, ENT_COMPAT);
    }
    echo $XML;
}
?>