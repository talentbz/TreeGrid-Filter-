<?php
// -----------------------------------------------------------------------------
// Two objects to access database via MySQL; works under any system (Windows, Linux, ...)
// You must create MySQL data source to the database first to use its name as parameter in Database constructor  
// Uses mysqli_ functions that are not in PHP4, for PHP4 use IncDbMySQLPHP4.php instead 
// -----------------------------------------------------------------------------
// Recordset created by Database::Execute SQL SELECT command; used to read-only access to returned table
class Recordset {
private $rs, $row;
function __construct($par){ $this->rs = $par; $this->First(); } // Do not call constructor directly 
function __destruct(){ }
function GetRowCount(){ return $this->rs->rowCount(); } // Returns number of records, can return -1 if no rows found
function First(){  } // Moves to first record, returns true on success, false on BOF
function Get($col){ return $this->row ? $this->row[$col] : NULL; }  // Returns value of the column value in actual row, column can be column name or index (from 0); if column does not exist, returns NULL
function GetRow(){ return $this->row; }  // Returns array of actual row's values 
function GetRows(){ 
	$arr = $this->rs->fetchAll(PDO::FETCH_ASSOC);
	return $arr; }                // Returns array of all rows in recordset
}
// -----------------------------------------------------------------------------
// Main object to connect to database; accepts database connection string as constructor parameter
class Database {
private $conn;
function Database($connstring) { $cs = explode("|",$connstring); $this->__construct($cs[0],$cs[1],$cs[2],$cs[3]); register_shutdown_function(array($this,"__destruct")); } //ConnectionString is in the form "DBname|[User]|[Password]|[Server[:port]]"
function __construct($name,$user="",$pass="",$server="localhost:3306"){
	try {
	  $this->conn = new PDO("mysql:host=$server;dbname=$name", $user, $pass);
	  // set the PDO error mode to exception
	  $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	} catch(PDOException $e) {
	  echo "Connection failed: " . $e->getMessage();
	  exit();
	}	
}
function getlastInsertId(){	$lastid = $this->conn->lastInsertId();	return $lastid; } 
function __destruct(){ if($this->conn) $this->conn =null; }
function Exec($cmd){	
	try {   
		$this->conn->prepare($cmd)->execute(); 
	} catch (PDOException $e) {   
		if (isset($e->errorInfo)) {   	
			return '-1';   
		} else {
		}		

	} 
}
function deleteonerror($id, $table){
	$this->Exec("DELETE FROM ".$table." WHERE id='".$id."'");
}
function deleteonerrorchild($id, $table){
	$this->Exec("DELETE FROM ".$table." WHERE parent='".$id."'");
}
function delete($cmd){
	try {
	    $stmt = $this->conn->prepare($cmd);
	    $rowdeleted = $stmt->execute();
	    $rowdeleted = $stmt->rowCount();
	} catch (PDOException $e) {
	   if (count($e->errorInfo) > 0) {
	      	return '-1';
		} else {
		    return $rowdeleted;
		}		
	} 
}
	function Query($cmd){ 
		$stmt = $this->conn->prepare($cmd);
		$stmt->execute();
		return $stmt ? new Recordset($stmt) : NULL; 
	}  // Executes SELECT command and returns opened recordset or NULL for other commands
	function movechildupdateprod($newparentnum, $movedid) {
      $childdata = $this->getoriginaldata($movedid, '*', 'production_orders_items');
      $originid = $this->getoriginaldata($childdata[0]['Parent'], 'warehouse_uuid', 'production_orders')[0]['warehouse_uuid'];
		$childitemqty = $childdata[0]['product_quantity'];
		$childitemuuid = $childdata[0]['item_uuid'];
		$childitemraw = $childdata[0]['raw_material_quantity'];

        if($this->minusupdateorcreatewarehouse($childitemuuid, $originid, $childitemqty)) {
	        if($this->updateorcreatewarehouse($childitemuuid, $originid, $childitemraw)) {
			} else {
              $this->updateorcreatewarehouse($itemuuid, $origin, $childitemqty);
				$displayerrors['error'][] = 'Can\'t update child there is some error';
				return false;
			}
		} else {
			$displayerrors['error'][] = 'Can\'t update child there is some error';
			return false;
		}

      $originid = $this->getoriginaldata($newparentnum, 'warehouse_uuid', 'production_orders')[0]['warehouse_uuid'];
		$childitemqty = $childdata[0]['product_quantity'];
		$childitemuuid = $childdata[0]['item_uuid'];
		$childitemraw = $childdata[0]['raw_material_quantity'];

        if($this->updateorcreatewarehouse($childitemuuid, $originid, $childitemqty)) {
	        if($this->minusupdateorcreatewarehouse($childitemuuid, $originid, $childitemraw)) {
			} else {
              $this->minusupdateorcreatewarehouse($itemuuid, $origin, $childitemqty);
				$displayerrors['error'][] = 'Can\'t update child there is some error';
				return false;
			}
		} else {
			$displayerrors['error'][] = 'Can\'t update child there is some error';
			return false;
		}

	}
	// function movechildupdatepayment($newparentnum, $movedid) {
 //      $childdata = $this->getoriginaldata($movedid, '*', 'payments_bills');
 //      $approved = $this->getoriginaldata($newparentnum, 'cashier_approve', 'payments')[0]['cashier_approve'];
 //      $oldapproved = $this->getoriginaldata($childdata[0]['Parent'], 'cashier_approve', 'payments')[0]['cashier_approve'];
	// 	if($approved) {
	// 		$paid = $childdata[0]['paid'];
	// 		$purchase_uuid = $childdata[0]['purchase_uuid'];

	//         if($this->updateorcreatewarehouse($childitemuuid, $originid, $childitemqty)) {
	// 		} else {
	// 			$displayerrors['error'][] = 'Can\'t update child there is some error';
	// 			return false;
	// 		}

	//       	$originid = $this->getoriginaldata($newparentnum, 'warehouse_uuid', 'payments')[0]['warehouse_uuid'];
	// 		$childitemqty = $childdata[0]['item_quantity'];
	// 		$childitemuuid = $childdata[0]['item_uuid'];
	//         if($this->minusupdateorcreatewarehouse($childitemuuid, $originid, $childitemqty)) {
	// 		} else {
	// 			$displayerrors['error'][] = 'Can\'t update child there is some error';
	// 			return false;
	// 		}
	// 	} else {

	// 	}
	// }


	function movechildupdatesales($newparentnum, $movedid) {
      $childdata = $this->getoriginaldata($movedid, '*', 'sales_items');
      $originid = $this->getoriginaldata($childdata[0]['Parent'], 'warehouse_uuid', 'sales')[0]['warehouse_uuid'];
      $approved = $this->getoriginaldata($childdata[0]['Parent'], 'document_manager_approval', 'sales')[0]['document_manager_approval'];
      $approvedsales = $this->getoriginaldata($childdata[0]['Parent'], 'sales_order_status', 'sales')[0]['sales_order_status'];
		if($approved) {
			$childitemqty = $childdata[0]['item_quantity'];
			$childitemuuid = $childdata[0]['item_uuid'];


	        if($this->updateorcreatewarehouse($childitemuuid, $originid, $childitemqty)) {
			} else {
				$displayerrors['error'][] = 'Can\'t update child there is some error';
				return false;
			}

	      	$originid = $this->getoriginaldata($newparentnum, 'warehouse_uuid', 'sales')[0]['warehouse_uuid'];
			$childitemqty = $childdata[0]['item_quantity'];
			$childitemuuid = $childdata[0]['item_uuid'];
	        if($this->minusupdateorcreatewarehouse($childitemuuid, $originid, $childitemqty)) {
			} else {
				$displayerrors['error'][] = 'Can\'t update child there is some error';
				return false;
			}
		}
		if($approvedsales) {
			$childitemqty = $childdata[0]['item_quantity'];
			$childitemuuid = $childdata[0]['item_uuid'];

	        if($this->minusupdateorcreatewarehouse($childitemuuid, $originid, $childitemqty, false, 'item_reserved_quantity')) {
			} else {
				$displayerrors['error'][] = 'Can\'t update child there is some error';
				return false;
			}

	      	$originid = $this->getoriginaldata($newparentnum, 'warehouse_uuid', 'sales')[0]['warehouse_uuid'];
			$childitemqty = $childdata[0]['item_quantity'];
			$childitemuuid = $childdata[0]['item_uuid'];
	        if($this->updateorcreatewarehouse($childitemuuid, $originid, $childitemqty, false, 'item_reserved_quantity')) {
			} else {
				$displayerrors['error'][] = 'Can\'t update child there is some error';
				return false;
			}
		}
	}


	function movechildupdateinvout($newparentnum, $movedid) {
      $childdata = $this->getoriginaldata($movedid, '*', 'inventiry_exit_items');
      $originid = $this->getoriginaldata($childdata[0]['Parent'], 'warehouse_uuid', 'inventory_exit')[0]['warehouse_uuid'];
		$childitemqty = $childdata[0]['item_quantity'];
		$childitemuuid = $childdata[0]['item_uuid'];

        if($this->minusupdateorcreatewarehouse($childitemuuid, $originid, $childitemqty)) {
		} else {
			$displayerrors['error'][] = 'Can\'t update child there is some error';
			return false;
		}

      $originid = $this->getoriginaldata($newparentnum, 'warehouse_uuid', 'inventory_exit')[0]['warehouse_uuid'];
		$childitemqty = $childdata[0]['item_quantity'];
		$childitemuuid = $childdata[0]['item_uuid'];
        if($this->updateorcreatewarehouse($childitemuuid, $originid, $childitemqty)) {
		} else {
			$displayerrors['error'][] = 'Can\'t update child there is some error';
			return false;
		}

	}

	function movechildupdatepurch($newparentnum, $movedid) {
      $childdata = $this->getoriginaldata($movedid, '*', 'purchases_items');
      $originid = $this->getoriginaldata($childdata[0]['Parent'], 'warehouse_uuid', 'purchases')[0]['warehouse_uuid'];
      $approved = $this->getoriginaldata($childdata[0]['Parent'], 'document_manager_approval', 'purchases')[0]['document_manager_approval'];
	  $approved2 = $childdata[0]['item_inventory_effect'];
		if($approved AND $approved2) {
			$childitemqty = $childdata[0]['item_quantity'];
			$childitemuuid = $childdata[0]['item_uuid'];

	        if($this->minusupdateorcreatewarehouse($childitemuuid, $originid, $childitemqty)) {
			} else {
				$displayerrors['error'][] = 'Can\'t update child there is some error';
				return false;
			}

	      	$originid = $this->getoriginaldata($newparentnum, 'warehouse_uuid', 'purchases')[0]['warehouse_uuid'];
			$childitemqty = $childdata[0]['item_quantity'];
			$childitemuuid = $childdata[0]['item_uuid'];
	        if($this->updateorcreatewarehouse($childitemuuid, $originid, $childitemqty)) {
			} else {
				$displayerrors['error'][] = 'Can\'t update child there is some error';
				return false;
			}
		}

	}

	function movechildupdateinv($newparentnum, $movedid) {
      $childdata = $this->getoriginaldata($movedid, '*', 'inventiry_entry_items');
      $originid = $this->getoriginaldata($childdata[0]['Parent'], 'warehouse_uuid', 'inventory_entry')[0]['warehouse_uuid'];
		$childitemqty = $childdata[0]['item_quantity'];
		$childitemuuid = $childdata[0]['item_uuid'];

        if($this->minusupdateorcreatewarehouse($childitemuuid, $originid, $childitemqty)) {
		} else {
			$displayerrors['error'][] = 'Can\'t update child there is some error';
			return false;
		}

      $originid = $this->getoriginaldata($newparentnum, 'warehouse_uuid', 'inventory_entry')[0]['warehouse_uuid'];
		$childitemqty = $childdata[0]['item_quantity'];
		$childitemuuid = $childdata[0]['item_uuid'];
        if($this->updateorcreatewarehouse($childitemuuid, $originid, $childitemqty)) {
		} else {
			$displayerrors['error'][] = 'Can\'t update child there is some error';
			return false;
		}

	}
	function movechildupdate($newparentnum, $movedid) {
      $childdata = $this->getoriginaldata($movedid, '*', 'transfers_items');
      $originid = $this->getoriginaldata($childdata[0]['Parent'], 'warehouse_origin_uuid', 'transfers')[0]['warehouse_origin_uuid'];
      $destnationid = $this->getoriginaldata($childdata[0]['Parent'], 'warehouse_destination_uuid', 'transfers')[0]['warehouse_destination_uuid'];
      $destinationapprove = $this->getoriginaldata($childdata[0]['Parent'], 'warehouseman_destination_approve', 'transfers')[0]['warehouseman_destination_approve'];
      if($destinationapprove == 1) {
		$childitemqty = $childdata[0]['item_quantity'];
		$childitemuuid = $childdata[0]['item_uuid'];
		if($this->reversecalculatewarehouse($childitemqty, $childitemuuid, $destnationid, $originid)) {
		} else {
			$displayerrors['error'][] = 'Can\'t update child there is some error';
			return false;
		}
      }

      $originid = $this->getoriginaldata($newparentnum, 'warehouse_origin_uuid', 'transfers')[0]['warehouse_origin_uuid'];
      $destnationid = $this->getoriginaldata($newparentnum, 'warehouse_destination_uuid', 'transfers')[0]['warehouse_destination_uuid'];
      if($destinationapprove == 1) {
		$childitemqty = $childdata[0]['item_quantity'];
		$childitemuuid = $childdata[0]['item_uuid'];
		if($this->calculatewarehouse($childitemqty, $childitemuuid, $destnationid, $originid)) {
		} else {
			$displayerrors['error'][] = 'Can\'t update child there is some error';
			return false;
		}
      }

	}
	function moveupdate($parenttable = 'transfers', $childtable = 'transfers_items', $movedid= 0, $newpos = 0, $forsign = '++', $level = 'parent') {
		if($level == 'child') {
			$oldparent = $this->Query("SELECT Parent FROM ".$childtable." WHERE id = '".$movedid."' ");
			$oldparent = $oldparent->GetRows();
			$oldparent = $oldparent[0]['Parent'];

            $gettotalparent = "SELECT id FROM ".$childtable." WHERE Parent = '".$oldparent."'";
            $gettotalparent = $this->Query($gettotalparent);
            $gettotalparent = $gettotalparent->GetRowCount();
            if($gettotalparent < 2) {
            	$S = "UPDATE `".$parenttable."` SET `has_child` = '0' WHERE id = '".$oldparent."' ";
            	$this->Exec($S);
            }

			$S = "UPDATE `".$childtable."` SET `Parent` = '".$newpos."' WHERE id = '".$movedid."' ";
			$this->Exec($S);

			$S = "UPDATE `".$parenttable."` SET `has_child` = '1' WHERE id = '".$newpos."' ";
			$this->Exec($S);


		} else {
			if($level == 'parent') {
				$S = "UPDATE `".$parenttable."` SET `grid_id` = '0', `id` = '0' WHERE id = '".$movedid."' ";
				$this->Exec($S);
			}

			$S = "UPDATE `".$childtable."` SET `Parent` = '0' WHERE Parent = '".$movedid."' ";
			$this->Exec($S);

			if($forsign == '++') {
				for ($i=$movedid; $i <= $newpos; $i++) { 
					$newid = $i-1;
					if($level == 'parent') {
						$S = "UPDATE `".$parenttable."` SET `grid_id` = '".$newid."', `id` = '".$newid."' WHERE id = '".$i."' ";
						$this->Exec($S);
					}

					$S = "UPDATE `".$childtable."` SET `Parent` = '".$newid."' WHERE Parent = '".$i."' ";
					$this->Exec($S);

					if($i == $newpos) {
						if($level == 'parent') {
							$S = "UPDATE `".$parenttable."` SET `grid_id` = '".$i."', `id` = '".$i."' WHERE id = '0' ";
							$this->Exec($S);
						}

						$S = "UPDATE `".$childtable."` SET `Parent` = '".$i."' WHERE Parent = '0' ";
						$this->Exec($S);
					}
				}
			} else {
				for ($i=$movedid; $i >= $newpos; $i--) { 
					$newid = $i+1;
					if($level == 'parent') {
						$S = "UPDATE `".$parenttable."` SET `grid_id` = '".$newid."', `id` = '".$newid."' WHERE id = '".$i."' ";
						$this->Exec($S);
					}

					$S = "UPDATE `".$childtable."` SET `Parent` = '".$newid."' WHERE Parent = '".$i."' ";
					$this->Exec($S);

					if($i == $newpos) {
						if($level == 'parent') {
							$S = "UPDATE `".$parenttable."` SET `grid_id` = '".$i."', `id` = '".$i."' WHERE id = '0' ";
							$this->Exec($S);
						}

						$S = "UPDATE `".$childtable."` SET `Parent` = '".$i."' WHERE Parent = '0' ";
						$this->Exec($S);
					}
				}
			}
		}
	}


	// ITEM 1 -> ITEM 1
	// WARE 2 -> WARE 1

	// ITEM 1 -> ITEM 2
	// WARE 2 -> WARE 2

	// ITEM 1 -> ITEM 2
	// WARE 2 -> WARE 1
	// Reverse Deduct from origin and add destination
	function reversecalculatewarehouse ($itemquantity, $itemuuid, $destination, $origin) {
		if($this->updateoriginware($itemuuid,$destination,$itemquantity)){
			$this->updateorcreatewarehouse($itemuuid, $origin, $itemquantity);
			return true;
        } else {
        	return false;
        }
	}
	// Deduct from origin and add destination
	function calculatewarehouse ($itemquantity, $itemuuid, $destination, $origin) {
		if($this->updateoriginware($itemuuid,$origin,$itemquantity)){
			$this->updateorcreatewarehouse($itemuuid, $destination, $itemquantity);
			return true;
        } else {
        	return false;
        }
	}
	function updateoriginware($itemuuid, $origin, $itemquantity) {
        $getwareitemdetailquery = "SELECT item_quanitity FROM warehouses_items_quantity WHERE item_quanitity >= '".$itemquantity."' AND warehouse_uuid = '".$origin."' AND item_uuid = '".$itemuuid."'";
        $getwareitemdetailquery = $this->Query($getwareitemdetailquery);
        $totalrow = $getwareitemdetailquery->GetRowCount();
        if($totalrow > 0) {
			$getrows = $getwareitemdetailquery->GetRows();

			$neworiginquantity = $getrows[0]['item_quanitity'] - $itemquantity;
			$S = "UPDATE `warehouses_items_quantity` SET `item_quanitity` = '".$neworiginquantity."' WHERE item_quanitity >= '".$itemquantity."' AND warehouse_uuid = '".$origin."' AND item_uuid = '".$itemuuid."'";
			$this->Exec($S);
			return true;
			exit();
		}		
		return false;
		exit();
	}
	function updateorcreatewarehouse ($itemid = 0, $warehouseid = 0, $itemquantity = 0, $skip = false, $qtyfield = 'item_quanitity') {
        $ifpostexistquery = $this->Query("SELECT id, ".$qtyfield." FROM warehouses_items_quantity WHERE item_uuid = '".$itemid."' AND warehouse_uuid = '".$warehouseid."'");
        $ifpostexist = $ifpostexistquery->GetRowCount();
        if($ifpostexist > 0) {
			$destneworiginquantity = $ifpostexistquery->GetRows();
			$destneworiginquantity = $itemquantity + $destneworiginquantity[0][$qtyfield];
			// UPDATE 
			if($destneworiginquantity >= 0) {
	        	$this->updatewarehouse("UPDATE `warehouses_items_quantity` SET `".$qtyfield."`='".$destneworiginquantity."' WHERE item_uuid = '".$itemid."' AND warehouse_uuid = '".$warehouseid."'");
	        	return true;
        	} else {
        		return false;
        	}
        } else {
			// INSERT 
			if($itemquantity >= 0) {
	        	$this->insertwarehouse("INSERT INTO `warehouses_items_quantity`(`warehouse_uuid`, `item_uuid`, `".$qtyfield."`) VALUES ('".$warehouseid."', '".$itemid."', '".$itemquantity."')");
	        	return true;
        	} else {
        		return false;
        	}
        }
	}
	function minusupdateorcreatewarehouse ($itemid = 0, $warehouseid = 0, $itemquantity = 0, $skip = false, $qtyfield = 'item_quanitity') {
        $ifpostexistquery = $this->Query("SELECT id, ".$qtyfield." FROM warehouses_items_quantity WHERE ".$qtyfield." >= '".$itemquantity."' AND item_uuid = '".$itemid."' AND warehouse_uuid = '".$warehouseid."' ");
        $ifpostexist = $ifpostexistquery->GetRowCount();
        if($ifpostexist > 0 || $skip) {
			$destneworiginquantity = $ifpostexistquery->GetRows();
			if(count($destneworiginquantity) > 0 ) {
				$destneworiginquantity = $destneworiginquantity[0][$qtyfield] - $itemquantity;

				// UPDATE 
	        	$this->updatewarehouse("UPDATE `warehouses_items_quantity` SET `".$qtyfield."`='".$destneworiginquantity."' WHERE item_uuid = '".$itemid."' AND warehouse_uuid = '".$warehouseid."'");
	        }
        	return true;
        }
    	return false;
	}
	function insertwarehouse($cmd) {
		$this->Exec($cmd);
	}
	function updatewarehouse($cmd) {
		$this->Exec($cmd);
	}

	function updatewarehouseqty($newqty, $item, $Warehouse, $field = 'item_quanitity') {
    	$this->updatewarehouse("UPDATE `warehouses_items_quantity` SET `".$field."`='".$newqty."' WHERE item_uuid = '".$item."' AND WAREHOUSE_UUID = '".$Warehouse."' ");
	}
	function updatewarehouseqty_entry($newqty, $item, $Warehouse, $field = 'item_quanitity') {
        $ifpostexistquery = $this->Query("SELECT * FROM warehouses_items_quantity WHERE item_uuid = '".$item."' AND WAREHOUSE_UUID = '".$Warehouse."' ");
        $ifpostexist = $ifpostexistquery->GetRowCount();
        if($ifpostexist > 0) {
	    	$this->updatewarehouse("UPDATE `warehouses_items_quantity` SET `".$field."`='".$newqty."' WHERE item_uuid = '".$item."' AND WAREHOUSE_UUID = '".$Warehouse."' ");
        } else {
			// INSERT 
        	$this->updatewarehouse("INSERT INTO `warehouses_items_quantity`(`warehouse_uuid`, `item_uuid`, `".$field."`) VALUES ('".$Warehouse."', '".$item."', '".$newqty."')");
        }

	}

	function getquantitydifference ($newqty = 0, $WHERE = 1, $field = 'item_quanitity') {
        $ifgetdata = $this->Query("SELECT * FROM warehouses_items_quantity WHERE ".$WHERE." ");
        $getdata = $this->Query("SELECT SUM(".$field." - '".$newqty."') AS newqty FROM warehouses_items_quantity WHERE ".$WHERE." ");
        $ifpostexist = $ifgetdata->GetRowCount();
        if($ifpostexist > 0) {
			$getdata = $getdata->GetRows()[0]['newqty'];
		} else {
			$getdata = -$newqty;
		}
		return $getdata;
	}
	function displayerror ($updatequery, $item_code, $item_name, $warehouse_name, $warehouse_code, $item_id, $postid) {
        $updatequery['error'][] = 'The inputed quantity for '.$item_name.' ' . $item_code . ' is not availeble in ' . $warehouse_name . ' '.$warehouse_code;
        $updatequery['givenitemid'][$item_id] = $item_id;
        $updatequery['itemid'][] = $item_id;
        $updatequery['break'][$postid] = '';
        return $updatequery;
	}
	function displayerroroninsert ($updatequery, $item_code, $item_name, $warehouse_name, $warehouse_code, $postid) {
        $updatequery['error'][] = 'The inputed quantity for '.$item_name.' ' . $item_code . ' is not availeble in ' . $warehouse_name . ' '.$warehouse_code;
        $updatequery['break'][$postid] = '';
        return $updatequery;
	}
	function getquantitysum ($newqty = 0, $WHERE = 1, $field = 'item_quanitity') {
        $ifgetdata = $this->Query("SELECT * FROM warehouses_items_quantity WHERE ".$WHERE." ");
        $getdata = $this->Query("SELECT SUM(".$field." + '".$newqty."') AS newqty FROM warehouses_items_quantity WHERE ".$WHERE." ");
        $ifpostexist = $ifgetdata->GetRowCount();
        if($ifpostexist > 0) {
			$getdata = $getdata->GetRows()[0]['newqty'];
		} else {
			$getdata = $newqty;
		}
		return $getdata;
	}

	function getoriginaldata ($id, $field, $table) {
        $getdata = $this->Query("SELECT ".$field." FROM ".$table." WHERE id = '".$id."'");
		$getdata = $getdata->GetRows();
		return $getdata;
	}
	function getoriginalparentdata ($id, $field, $table) {
        $getdata = $this->Query("SELECT ".$field." FROM ".$table." WHERE Parent = '".$id."'");
		$getdata = $getdata->GetRows();
		return $getdata;
	}
	function getparentdetail ($id, $table) {
        $getparent = $this->Query("SELECT * FROM ".$table." WHERE id = '".$id."'");
		$getparent = $getparent->GetRows();
		return $getparent;
		exit();
		return 0;
	}
	function getall ($table) {
        $getparent = $this->Query("SELECT * FROM ".$table." ");
		$getparent = $getparent->GetRows();
		return $getparent;
		exit();
		return 0;
	}

	function addpurchase($uuid, $paid = 0, $minus = false) {
        $ifpostexistquery = $this->Query("SELECT id, paid FROM purchases WHERE purchase_uuid = '".$uuid."'");
        $ifpostexist = $ifpostexistquery->GetRowCount();
        if($ifpostexist > 0) {
			$paidamount = $ifpostexistquery->GetRows();
			if($minus) {
				$paidamount = $paidamount[0]['paid'] - $paid;
			} else {
				$paidamount = $paid + $paidamount[0]['paid'];
			}

			// UPDATE 
        	$this->updatewarehouse("UPDATE `purchases` SET `paid`='".$paidamount."' WHERE purchase_uuid = '".$uuid."' ");
        	return true;
        } else {
	        $getlatestid = $this->Query("SELECT id FROM purchases WHERE purchase_uuid = '".$uuid."' ORDER BY id desc LIMIT 1");
			$getlatestid = $getlatestid->GetRows()[0]['id'];
			$id = $getlatestid;
	        do {
				$addparent = $this->Exec("INSERT INTO purchases(`id`, `grid_id`, `paid`, `purchase_uuid`) VALUES('". $id ."','". $id ."','". $paidamount ."','". $uuid ."')");
	            if ($addparent != '-1') {
	            } else {
	              $id = $id + 1;
	            }
	        } while ($addparent == '-1');
        	return true;
        }
	}
	function updatebankonpayment($uuid, $paid = 0, $minus = false, $skip = false) {
        $ifpostexistquery = $this->Query("SELECT id, balance FROM banks WHERE uuid = '".$uuid."'");
        $ifpostexist = $ifpostexistquery->GetRowCount();
        if($ifpostexist > 0) {
			$paidamount = $ifpostexistquery->GetRows();
			if($minus) {
				$paidamount = $paidamount[0]['balance'] - $paid;
			} else {
				$paidamount = $paid + $paidamount[0]['balance'];
			}
        	$this->updatewarehouse("UPDATE `banks` SET `balance`='".$paidamount."' WHERE uuid = '".$uuid."' ");
        	return true;
        }
	}
	function addsales($uuid, $paid = 0, $minus = false) {
        $ifpostexistquery = $this->Query("SELECT id, paid FROM sales WHERE uuid = '".$uuid."'");
        $ifpostexist = $ifpostexistquery->GetRowCount();
        if($ifpostexist > 0) {
			$paidamount = $ifpostexistquery->GetRows();
			if($minus) {
				$paidamount = $paidamount[0]['paid'] - $paid;
			} else {
				$paidamount = $paid + $paidamount[0]['paid'];
			}

			// UPDATE 
        	$this->updatewarehouse("UPDATE `sales` SET `paid`='".$paidamount."' WHERE uuid = '".$uuid."' ");
        	return true;
        } else {
	        $getlatestid = $this->Query("SELECT id FROM sales WHERE uuid = '".$uuid."' ORDER BY id desc LIMIT 1");
			$getlatestid = $getlatestid->GetRows()[0]['id'];
			$id = $getlatestid;
	        do {
				$addparent = $this->Exec("INSERT INTO sales(`id`, `grid_id`, `paid`, `uuid`) VALUES('". $id ."','". $id ."','". $paidamount ."','". $uuid ."')");
	            if ($addparent != '-1') {
	            } else {
	              $id = $id + 1;
	            }
	        } while ($addparent == '-1');
        	return true;
        }
	}
	function generatedocumentno($tablename, $documentno, $documentabbrevation) {
        $selectdata = $this->Query("SELECT document_no FROM ".$tablename." ORDER BY document_no DESC");
        $selectdata = $selectdata->GetRows();

        if(empty($documentno)) {
          if(count($selectdata) > 0) {
            foreach ($selectdata as $row) {
              $allnum[] = filter_var($row['document_no'], FILTER_SANITIZE_NUMBER_INT);
            }
            rsort($allnum);

            if(!empty($allnum[0])) {
              $documentno = $allnum[0] + 1;
            } else {
              $documentno = '00001';
            }
          } else {
            $documentno = '00001';
          }
        } else {
          $documentno = filter_var($documentno, FILTER_SANITIZE_NUMBER_INT);
        }
        if(strlen($documentno) == 1) {
          $documentno = $documentabbrevation.'0000'.$documentno;
        } elseif(strlen($documentno) == 2) {
          $documentno = $documentabbrevation.'000'.$documentno;
        } elseif(strlen($documentno) == 3) {
          $documentno = $documentabbrevation.'00'.$documentno;
        } elseif(strlen($documentno) == 4) {
          $documentno = $documentabbrevation.'0'.$documentno;
        } elseif(strlen($documentno) == 5) {
          $documentno = $documentabbrevation.$documentno;
        }
        return $documentno;
	}
	function getwarehousename($table, $id, $where, $select) {
        $returnname = $this->Query("SELECT ".$select." FROM ".$table." WHERE ".$where." = '".$id."' LIMIT 1");
		$returnname = $returnname->GetRows();
		if(isset($returnname[0][$select])) {
			return $returnname[0][$select];
		} else {
			return null;
		}
	}

	function getwarename($table, $id, $where, $select, $select2) {
        $returnname = $this->Query("SELECT ".$select.", ".$select2." FROM ".$table." WHERE ".$where." = '".$id."' LIMIT 1");
		$returnname = $returnname->GetRows();
		if(isset($returnname[0][$select])) {
			return $returnname[0][$select].' '.$returnname[0][$select2];
		} else {
			return null;
		}
	}
    function xml2array ( $xmlObject, $out = array () )
    {
        foreach ( (array) $xmlObject as $index => $node )
            $out[$index] = ( is_object ( $node ) ) ? xml2array ( $node ) : $node;

        return $out;
    }
    
    function upload_stock_quantity_cost_transfer($newqty = '', $itemuuid = '', $warehouse = '', $childtable = '', $childid = '', $insert_true = 0, $delete_true = 0, $isupload_old_true = 0, $transfer = '') {
        $get_child = $this->Query("SELECT *, ".$childtable.".id as item_id FROM ".$childtable." WHERE ".$childtable.".id = '".$childid."'");
		$get_child = $get_child->GetRows();
		$get_child = $get_child[0];

        $get_warehouse = $this->Query("SELECT * FROM warehouses_items_quantity WHERE item_uuid = '".$itemuuid."' AND warehouse_uuid = '".$warehouse."'");
		$get_warehouse = $get_warehouse->GetRows();
		$get_warehouse = $get_warehouse[0];


		if($isupload_old_true == 1) {
			$item_stock_value = $get_warehouse['item_stock_value'] + $get_child['item_cost_quantity'];

	    	$S = "UPDATE `warehouses_items_quantity` SET `item_stock_value` = '".$item_stock_value."' WHERE item_uuid = '".$itemuuid."' AND warehouse_uuid = '".$warehouse."' ";
	    	$this->Exec($S);
	    	// echo $S;
	    	// echo "<br>";
	    	$S = "UPDATE `".$childtable."` SET `item_cost_quantity` = '0', `item_cost` = '0' WHERE id = '".$childid."' ";
	    	$this->Exec($S);
	    	// echo $S;
	    	// echo "<br>";
		} else {
			if($delete_true == 1) {
				if($transfer == '') {
					$item_stock_value = $get_warehouse['item_stock_value'] + $get_child['item_cost_quantity'];
				} else {
					$item_stock_value = $get_warehouse['item_stock_value'] - $get_child['item_cost_quantity'];
				}


		    	$S = "UPDATE `warehouses_items_quantity` SET `item_stock_value` = '".$item_stock_value."' WHERE item_uuid = '".$itemuuid."' AND warehouse_uuid = '".$warehouse."' ";
		    	$this->Exec($S);
		    	// echo $S;
		    	// echo "<br>";
			} else {
				$temp_old_qty = $get_child['item_quantity'];
				if($insert_true) {
					$temp_new_qty = $newqty;
					$temp_ware_house_item_quantity = $get_warehouse['item_quanitity'];
				} else {
					$temp_new_qty = $newqty + $temp_old_qty;
					if($transfer == '') {
						$temp_ware_house_item_quantity = $get_warehouse['item_quanitity'] + $temp_old_qty;
					} else {
						$temp_ware_house_item_quantity = $get_warehouse['item_quanitity'] - $temp_old_qty;
					}

				}

				if($transfer == '') {
					$temp_new_item_stock_value = $get_warehouse['item_stock_value'] + $get_child['item_cost_quantity'];
				} else {
					$new_temp_item_cost_quantity = $get_child['item_cost_quantity'];
					$temp_new_item_stock_value = $get_warehouse['item_stock_value'] - $new_temp_item_cost_quantity;
				}


				$item_cost = $temp_new_item_stock_value / $temp_ware_house_item_quantity;
				$item_cost_quantity = $item_cost * $temp_new_qty;
				if($transfer == '') {
					$item_stock_value = $temp_new_item_stock_value - $item_cost_quantity;
				} else {
					$item_stock_value = $temp_new_item_stock_value + $item_cost_quantity;
				}
		    	// if($warehouse == 2){
		    	// 	echo $temp_old_qty;
		    	// 	echo '<br>';
		    	// 	echo $temp_ware_house_item_quantity;
		    	// 	echo '<br>';
		    	// 	echo $item_stock_value;
		    	// 	echo '<br>';
		    	// 	echo $temp_new_qty;
		    	// 	echo '<br>';
		    	// 	echo $item_cost;
		    	// 	echo '<br>';
		    	// 	echo $item_cost_quantity;
		    	// 	echo '<br>';
		    	// }

				// if($transfer != '') {
				// 	echo $item_stock_value;
				// 	echo "<br>";
				// 	exit();
				// }
		    	$S = "UPDATE `warehouses_items_quantity` SET `item_stock_value` = '".$item_stock_value."' WHERE item_uuid = '".$itemuuid."' AND warehouse_uuid = '".$warehouse."' ";
		    	$this->Exec($S);
		    	// echo $S;
		    	// echo "<br>";

				if($transfer == '') {
			    	$S = "UPDATE `".$childtable."` SET `item_cost_quantity` = '".$item_cost_quantity."', `item_cost` = '".$item_cost."' WHERE id = '".$childid."' ";
			    	// echo $S;
			    	// echo "<br>";
			    	$this->Exec($S);
			    }
		    }
	    }
    }


    function get_item_stock_value_delete($childtable = '', $childid = '', $itemuuid = '', $warehouse = '', $new_qty = '', $add = 0, $json_data = '', $get_new_qty = '') {

	    $get_child = $this->Query("SELECT *, ".$childtable.".id as item_id FROM ".$childtable." WHERE ".$childtable.".id = '".$childid."'");
		$get_child = $get_child->GetRows();
		$get_child = $get_child[0];

	    $get_warehouse = $this->Query("SELECT * FROM warehouses_items_quantity WHERE item_uuid = '".$itemuuid."' AND warehouse_uuid = '".$warehouse."'");
		$get_warehouse = $get_warehouse->GetRows();
		$get_warehouse = $get_warehouse[0];

    	if(!empty($json_data)) {
    		$json_data = json_decode($json_data);
    		$item_cost_array = json_decode($json_data->item_cost);
    		$item_cost_quantity_array = json_decode($json_data->item_cost_quantity);
    		$get_warehouse['item_stock_value'] = $json_data->item_stock_value;
    		if($add) {
    			$get_new_qty = $get_new_qty + $new_qty;
    		} else {
    			$get_new_qty = $get_new_qty - $new_qty;
    		}
    		$get_warehouse['item_quanitity'] = $get_new_qty;
    	}

		if($add) {
			$item_stock_value = $get_warehouse['item_stock_value'] - $get_child['item_cost_quantity'];
		} else {
			$item_stock_value = $get_warehouse['item_stock_value'] + $get_child['item_cost_quantity'];
		}

		$item_cost_quantity_array = json_encode(array("value"=>'0', "id"=> $childid));
		$item_cost_array = json_encode(array("value"=>'0', "id"=> $childid));
		return array("item_cost"=>$item_cost_array,"item_cost_quantity"=>$item_cost_quantity_array,"item_stock_value"=>$item_stock_value);
    }


    function get_item_stock_value_insert($childtable = '', $childid = '', $itemuuid = '', $warehouse = '', $new_qty = '', $add = 0, $json_data = '', $get_new_qty = '') {
	    $get_child = $this->Query("SELECT *, ".$childtable.".id as item_id FROM ".$childtable." WHERE ".$childtable.".id = '".$childid."'");
		$get_child = $get_child->GetRows();
		$get_child = $get_child[0];

	    $get_warehouse = $this->Query("SELECT * FROM warehouses_items_quantity WHERE item_uuid = '".$itemuuid."' AND warehouse_uuid = '".$warehouse."'");
		$get_warehouse = $get_warehouse->GetRows();
		if(isset($get_warehouse[0])) {
			$get_warehouse = $get_warehouse[0];
		} else {
			$get_warehouse['item_stock_value'] = 1;
			$get_warehouse['item_quanitity'] = 1;
		}

		$old_qty = $get_child['item_quantity'];

    	if(!empty($json_data)) {
    		$json_data = json_decode($json_data);
    		$item_cost_array = json_decode($json_data->item_cost);
    		$item_cost_quantity_array = json_decode($json_data->item_cost_quantity);
    		$get_warehouse['item_stock_value'] = $json_data->item_stock_value;
    		if($add) {
    			$get_new_qty = $get_new_qty + $new_qty;
    		} else {
    			$get_new_qty = $get_new_qty - $new_qty;
    		}
    		$get_warehouse['item_quanitity'] = $get_new_qty;
    	}

		if($add) {
			$get_warehouse['item_stock_value'] = $get_warehouse['item_stock_value'] + $get_child['item_cost_quantity'];
		} else {
			$get_warehouse['item_stock_value'] = $get_warehouse['item_stock_value'] - $get_child['item_cost_quantity'];
		}

		$item_cost = $get_warehouse['item_stock_value'] / $get_warehouse['item_quanitity'];
		$item_cost_quantity = $item_cost * $new_qty;
		if($add) {
			$item_stock_value = $get_warehouse['item_stock_value'] - $item_cost_quantity;
		} else {
			$item_stock_value = $get_warehouse['item_stock_value'] + $item_cost_quantity;
		}
		$item_cost_quantity_array = json_encode(array("value"=>$item_cost_quantity, "id"=> $childid));
		$item_cost_array = json_encode(array("value"=>$item_cost, "id"=> $childid));
		return array("item_cost"=>$item_cost_array,"item_cost_quantity"=>$item_cost_quantity_array,"item_stock_value"=>$item_stock_value);
    }

    function get_item_stock_value_child_incomplete_qty($childtable = '', $childid = '', $itemuuid = '', $warehouse = '', $new_qty = '', $add = 0, $complete_qty = 0, $json_data = '', $get_new_qty = '') {
	    $get_child = $this->Query("SELECT *, ".$childtable.".id as item_id FROM ".$childtable." WHERE ".$childtable.".id = '".$childid."'");
		$get_child = $get_child->GetRows();
		$get_child = $get_child[0];

	    $get_warehouse = $this->Query("SELECT * FROM warehouses_items_quantity WHERE item_uuid = '".$itemuuid."' AND warehouse_uuid = '".$warehouse."'");
		$get_warehouse = $get_warehouse->GetRows();
		$get_warehouse = $get_warehouse[0];

		$old_qty = $get_child['item_quantity'];
		if($complete_qty != 0) {
			$new_qty = $new_qty + $get_child['item_quantity'];
		}

    	if(!empty($json_data)) {
    		$json_data = json_decode($json_data);
    		$item_cost_array = json_decode($json_data->item_cost);
    		$item_cost_quantity_array = json_decode($json_data->item_cost_quantity);
    		$get_warehouse['item_stock_value'] = $json_data->item_stock_value;
    		if($add) {
    			$get_new_qty = $get_new_qty + $new_qty;
    		} else {
    			$get_new_qty = $get_new_qty - $new_qty;
    		}
    		$get_warehouse['item_quanitity'] = $get_new_qty;
    	}

		if($add) {
			$get_warehouse['item_stock_value'] = $get_warehouse['item_stock_value'] + $get_child['item_cost_quantity'];
			$get_warehouse['item_quanitity'] = $get_warehouse['item_quanitity'] + $get_child['item_quantity'];
		} else {
			$get_warehouse['item_stock_value'] = $get_warehouse['item_stock_value'] - $get_child['item_cost_quantity'];
				$get_warehouse['item_quanitity'] = $get_warehouse['item_quanitity'] - $get_child['item_quantity'];
		}

		$item_cost = $get_warehouse['item_stock_value'] / $get_warehouse['item_quanitity'];
		$item_cost_quantity = $item_cost * $new_qty;
		if($add) {
			$item_stock_value = $get_warehouse['item_stock_value'] - $item_cost_quantity;
		} else {
			$item_stock_value = $get_warehouse['item_stock_value'] + $item_cost_quantity;
		}

		$item_cost_quantity_array = json_encode(array("value"=>$item_cost_quantity, "id"=> $childid));
		$item_cost_array = json_encode(array("value"=>$item_cost, "id"=> $childid));
		return array("item_cost"=>$item_cost_array,"item_cost_quantity"=>$item_cost_quantity_array,"item_stock_value"=>$item_stock_value);
    }
    function get_item_stock_value_child_complete_qty($childtable = '', $childid = '', $itemuuid = '', $warehouse = '', $new_qty = '', $add = 0, $complete_qty = 0, $json_data = '', $get_new_qty = '') {
	    $get_child = $this->Query("SELECT *, ".$childtable.".id as item_id FROM ".$childtable." WHERE ".$childtable.".id = '".$childid."'");
		$get_child = $get_child->GetRows();
		$get_child = $get_child[0];

	    $get_warehouse = $this->Query("SELECT * FROM warehouses_items_quantity WHERE item_uuid = '".$itemuuid."' AND warehouse_uuid = '".$warehouse."'");
		$get_warehouse = $get_warehouse->GetRows();
		$get_warehouse = $get_warehouse[0];

		$old_qty = $get_child['item_quantity'];
		if($complete_qty != 0) {
			$new_qty = $new_qty + $get_child['item_quantity'];
		}

    	if(!empty($json_data)) {
    		$json_data = json_decode($json_data);
    		$item_cost_array = json_decode($json_data->item_cost);
    		$item_cost_quantity_array = json_decode($json_data->item_cost_quantity);
    		$get_warehouse['item_stock_value'] = $json_data->item_stock_value;
    		if($add) {
    			$get_new_qty = $get_new_qty + $new_qty;
    		} else {
    			$get_new_qty = $get_new_qty - $new_qty;
    		}
    		$get_warehouse['item_quanitity'] = $get_new_qty;
    	}

		if($add) {
			if($complete_qty != 0) {
				$get_warehouse['item_stock_value'] = $get_warehouse['item_stock_value'] - $get_child['item_cost_quantity'];
				$get_warehouse['item_quanitity'] = $get_warehouse['item_quanitity'] -  $get_child['item_quantity'];
			}
		} else {
			if($complete_qty != 0) {
				$get_warehouse['item_stock_value'] = $get_warehouse['item_stock_value'] + $get_child['item_cost_quantity'];
				$get_warehouse['item_quanitity'] = $get_warehouse['item_quanitity'] +  $get_child['item_quantity'];
			}
		}

		$item_cost = $get_warehouse['item_stock_value'] / $get_warehouse['item_quanitity'];
		$item_cost_quantity = $item_cost * $new_qty;
		if($add) {
			$item_stock_value = $get_warehouse['item_stock_value'] - $item_cost_quantity;
		} else {
			$item_stock_value = $get_warehouse['item_stock_value'] + $item_cost_quantity;
		}

		$item_cost_quantity_array = json_encode(array("value"=>$item_cost_quantity, "id"=> $childid));
		$item_cost_array = json_encode(array("value"=>$item_cost, "id"=> $childid));
		return array("item_cost"=>$item_cost_array,"item_cost_quantity"=>$item_cost_quantity_array,"item_stock_value"=>$item_stock_value);
    }

    function get_item_stock_value_parent($childtable = '', $childid = '', $itemuuid = '', $warehouse = '', $new_qty = '', $add = 0, $complete_qty = 0, $json_data = '', $get_new_qty = '') {
	    $get_child = $this->Query("SELECT *, ".$childtable.".id as item_id FROM ".$childtable." WHERE ".$childtable.".id = '".$childid."'");
		$get_child = $get_child->GetRows();
		$get_child = $get_child[0];

	    $get_warehouse = $this->Query("SELECT * FROM warehouses_items_quantity WHERE item_uuid = '".$itemuuid."' AND warehouse_uuid = '".$warehouse."'");
		$get_warehouse = $get_warehouse->GetRows();
		if(isset($get_warehouse[0])) {
			$get_warehouse = $get_warehouse[0];
		} else {
			$get_warehouse['item_stock_value'] = 1;
			$get_warehouse['item_quanitity'] = 1;
		}

		$old_qty = $get_child['item_quantity'];
		if($complete_qty != 0) {
			$new_qty = $new_qty + $get_child['item_quantity'];
		}
    	if(!empty($json_data)) {
    		$json_data = json_decode($json_data);
    		$item_cost_array = json_decode($json_data->item_cost);
    		$item_cost_quantity_array = json_decode($json_data->item_cost_quantity);
    		$get_warehouse['item_stock_value'] = $json_data->item_stock_value;
    		if($add) {
    			$get_new_qty = $get_new_qty + $new_qty;
    		} else {
    			$get_new_qty = $get_new_qty - $new_qty;
    		}
    		$get_warehouse['item_quanitity'] = $get_new_qty;
    	}
		if($add) {
			if($complete_qty != 0) {
				$get_warehouse['item_stock_value'] = $get_warehouse['item_stock_value'] - $get_child['item_cost_quantity'];
				$get_warehouse['item_quanitity'] = $get_warehouse['item_quanitity'] -  $get_child['item_quantity'];
			}
		} else {
			if($complete_qty != 0) {
				$get_warehouse['item_stock_value'] = $get_warehouse['item_stock_value'] + $get_child['item_cost_quantity'];
				$get_warehouse['item_quanitity'] = $get_warehouse['item_quanitity'] +  $get_child['item_quantity'];
			}
		}

		$item_cost = $get_warehouse['item_stock_value'] / $get_warehouse['item_quanitity'];
		$item_cost_quantity = $item_cost * $new_qty;
		if($add) {
			$item_stock_value = $get_warehouse['item_stock_value'] - $item_cost_quantity;
		} else {
			$item_stock_value = $get_warehouse['item_stock_value'] + $item_cost_quantity;
		}

		$item_cost_quantity_array = json_encode(array("value"=>$item_cost_quantity, "id"=> $childid));
		$item_cost_array = json_encode(array("value"=>$item_cost, "id"=> $childid));
		return array("item_cost"=>$item_cost_array,"item_cost_quantity"=>$item_cost_quantity_array,"item_stock_value"=>$item_stock_value);
    }


    function update_item_stock_value($value, $item, $warehouse) {
    	$S = "UPDATE `warehouses_items_quantity` SET `item_stock_value` = '".$value."' WHERE item_uuid = '".$item."' AND warehouse_uuid = '".$warehouse."' ";
    	$this->Exec($S);
    }
    function update_item_cost($value, $table) {
    	$value = json_decode($value);
    	$S = "UPDATE `".$table."` SET `item_cost` = '".$value->value."' WHERE id = '".$value->id."' ";
    	$this->Exec($S);
    }
    function update_item_cost_quantity($value, $table) {
    	$value = json_decode($value);
    	$S = "UPDATE `".$table."` SET `item_cost_quantity` = '".$value->value."' WHERE id = '".$value->id."' ";
    	$this->Exec($S);
    }

	function upload_stock_quantity_cost($newqty = '', $itemuuid = '', $warehouse = '', $childtable = '', $childid = '', $insert_true = 0, $delete_true = 0, $isupload_old_true = 0, $transfer = '') {

	    $get_child = $this->Query("SELECT *, ".$childtable.".id as item_id FROM ".$childtable." WHERE ".$childtable.".id = '".$childid."'");
		$get_child = $get_child->GetRows();
		$get_child = $get_child[0];

	    $get_warehouse = $this->Query("SELECT * FROM warehouses_items_quantity WHERE item_uuid = '".$itemuuid."' AND warehouse_uuid = '".$warehouse."'");
		$get_warehouse = $get_warehouse->GetRows();
		$get_warehouse = $get_warehouse[0];


		if($isupload_old_true == 1) {
			$item_stock_value = $get_warehouse['item_stock_value'] + $get_child['item_cost_quantity'];

	    	$S = "UPDATE `warehouses_items_quantity` SET `item_stock_value` = '".$item_stock_value."' WHERE item_uuid = '".$itemuuid."' AND warehouse_uuid = '".$warehouse."' ";
	    	$this->Exec($S);

	    	$S = "UPDATE `".$childtable."` SET `item_cost_quantity` = '0', `item_cost` = '0' WHERE id = '".$childid."' ";
	    	$this->Exec($S);
		} else {
			if($delete_true == 1) {
				if($transfer == '') {
					$item_stock_value = $get_warehouse['item_stock_value'] + $get_child['item_cost_quantity'];
				} else {
					$item_stock_value = $get_warehouse['item_stock_value'] - $get_child['item_cost_quantity'];
				}


		    	$S = "UPDATE `warehouses_items_quantity` SET `item_stock_value` = '".$item_stock_value."' WHERE item_uuid = '".$itemuuid."' AND warehouse_uuid = '".$warehouse."' ";
		    	$this->Exec($S);
			} else {
				$temp_old_qty = $get_child['item_quantity'];
				if($insert_true) {
					$temp_new_qty = $newqty;
					$temp_ware_house_item_quantity = $get_warehouse['item_quanitity'];
				} else {
					$temp_new_qty = $newqty + $temp_old_qty;
					$temp_ware_house_item_quantity = $get_warehouse['item_quanitity'] + $temp_old_qty;
				}

				$temp_new_item_stock_value = $get_warehouse['item_stock_value'] + $get_child['item_cost_quantity'];


				$item_cost = $temp_new_item_stock_value / $temp_ware_house_item_quantity;
				$item_cost_quantity = $item_cost * $temp_new_qty;
				if($transfer == '') {
					$item_stock_value = $temp_new_item_stock_value - $item_cost_quantity;
				} else {
					$item_stock_value = $temp_new_item_stock_value + $item_cost_quantity;
				}

		    	$S = "UPDATE `warehouses_items_quantity` SET `item_stock_value` = '".$item_stock_value."' WHERE item_uuid = '".$itemuuid."' AND warehouse_uuid = '".$warehouse."' ";
		    	$this->Exec($S);

				if($transfer == '') {
			    	$S = "UPDATE `".$childtable."` SET `item_cost_quantity` = '".$item_cost_quantity."', `item_cost` = '".$item_cost."' WHERE id = '".$childid."' ";
			    }
		    	$this->Exec($S);
		    }
	    }
	}

}

// -----------------------------------------------------------------------------
?>