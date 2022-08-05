package main

import (
	"context"
	"database/sql"
	"encoding/base64"
	"encoding/json"
	"fmt"
	"log"
	"math"
	"net/url"
	"os"
	"strconv"
	"strings"
	"time"

	"github.com/aws/aws-lambda-go/events"
	"github.com/aws/aws-lambda-go/lambda"
	_ "github.com/go-sql-driver/mysql"
)

// declare required variables
var db *sql.DB

var ItemsFields, fieldAliases, fieldAliasesDate, filterWhere map[string]string
var filterArgs map[string][]interface{}
var GLOBALS = make(map[string]interface{})

var pageSize int = 100

var parentQueryJoin = "INNER JOIN documents ON transfers.document_type_uuid = documents.id  INNER JOIN stores ON transfers.store_origin_uuid = stores.id INNER JOIN stores  ss ON transfers.store_destination_uuid = ss.id  INNER JOIN warehouses ON transfers.warehouse_origin_uuid = warehouses.id INNER JOIN warehouses w2 ON transfers.warehouse_destination_uuid = w2.id  INNER JOIN responsibility_center ON transfers.responsibility_center_uuid = responsibility_center.id "

// Dummy where clause used before AND clauses
var dummyWhere = " where 1=1 "

var childQueryJoin = " INNER JOIN items ON transfers_items.item_uuid = items.id  INNER JOIN units ON transfers_items.item_unit_uuid = units.id INNER JOIN item_types ON items.type_uuid = item_types.id "

func init() {
	fmt.Fprintf(os.Stderr, "init")
	// Realed to transfer data
	ItemsFields = make(map[string]string)
	ItemsFields["item_type"] = "transfers_items.item_type"
	ItemsFields["item_no"] = "items.no"
	ItemsFields["item_name"] = "items.description"
	ItemsFields["item_unit"] = "units.code"
	ItemsFields["input_quantity"] = "transfers_items.input_quantity"
	ItemsFields["item_quantity_unit"] = "transfers_items.item_quantity_unit"
	ItemsFields["item_quantity"] = "transfers_items.item_quantity"
	ItemsFields["item_tempory"] = "transfers_items.item_tempory"
	ItemsFields["item_uuid"] = "transfers_items.item_uuid"
	ItemsFields["item_unit_uuid"] = "transfers_items.item_unit_uuid"
	ItemsFields["item_code"] = "transfers_items.item_code"
	ItemsFields["item_barcode"] = "transfers_items.item_barcode"
	ItemsFields["item_brand"] = "transfers_items.item_brand"
	ItemsFields["item_category"] = "transfers_items.item_category"
	ItemsFields["item_subcategory"] = "transfers_items.item_subcategory"

	fieldAliases = make(map[string]string)
	fieldAliases["document_abbrevation"] = "transfers.document_abbrevation"
	fieldAliases["document_type"] = "transfers.document_type"
	fieldAliases["store_origin_code"] = "stores.code"
	fieldAliases["store_origin_code"] = "stores.code"
	fieldAliases["warehouse_origin_code"] = "warehouses.code"
	fieldAliases["warehouse_destination_code"] = "warehouses.code"
	fieldAliases["store_destination_code"] = "stores.code"
	fieldAliases["responsibility_center"] = `responsibility_center.code`
	fieldAliases["document_date"] = `STR_TO_DATE(document_date,'%m/%d/%Y')`
	fieldAliases["posting_date"] = `STR_TO_DATE(posting_date,'%m/%d/%Y')`
	fieldAliases["delivery_date"] = `STR_TO_DATE(delivery_date,'%m/%d/%Y')`
	fieldAliases["entry_date"] = `STR_TO_DATE(entry_date,'%m/%d/%Y')`

	fieldAliasesDate = make(map[string]string)
	fieldAliasesDate["1"] = " = "
	fieldAliasesDate["2"] = " != "
	fieldAliasesDate["3"] = " < "
	fieldAliasesDate["4"] = " <= "
	fieldAliasesDate["5"] = " > "
	fieldAliasesDate["6"] = " >= "

	filterWhere = make(map[string]string)
	filterArgs = make(map[string][]interface{})
	GLOBALS["transfer_items_fields"] = []string{"item_uuid", "item_name", "item_code", "item_type", "item_barcode", "item_brand", "item_category", "item_subcategory", "item_unit", "item_quantity"}
}

func main() {
	fmt.Fprintf(os.Stderr, "main")
	initDb()
	lambda.Start(GetTransfersData)
}

func initDb() {
	var err error
	dbconf, err := os.ReadFile("./config/db-local.conf")
	if err != nil {
		log.Fatalln(err)
		fmt.Fprintf(os.Stderr, "err1: %s", err)
	}

	db, err = sql.Open("mysql", string(dbconf))

	if err != nil {
		fmt.Fprintf(os.Stderr, "err2: %s", err)
		log.Fatalln(err)
	}
}

func isDateValue(stringDate string) bool {
	_, err := time.Parse("01/02/2006", stringDate)
	return err == nil
}

func prepareFilterbyDeli(curMarker string, curField string, curFieldValue string, filterWhere map[string]string, filterArgs map[string][]interface{}, condition string, conditionVal string, modPosition string) {
	// Check if filter have any delimited values
	splitValue := strings.Split(curFieldValue, ";")
	// Check if filter have any Range values
	rangeValue := strings.Split(curFieldValue, "~")
	inValues := strings.Split(curFieldValue, ",")
	if len(splitValue) > 1 {
		for i := 0; i < len(splitValue); i++ {
			if i == 0 {
				filterWhere[curMarker] += condition + curField + conditionVal
			} else {
				filterWhere[curMarker] += " OR " + curField + conditionVal
			}
			if modPosition == "both" {
				filterArgs[curMarker] = append(filterArgs[curMarker], "%"+splitValue[i]+"%")
			} else if modPosition == "start" {
				filterArgs[curMarker] = append(filterArgs[curMarker], "%"+splitValue[i])
			} else if modPosition == "end" {
				filterArgs[curMarker] = append(filterArgs[curMarker], splitValue[i]+"%")
			} else {
				filterArgs[curMarker] = append(filterArgs[curMarker], splitValue[i])
			}
		}
	} else if len(rangeValue) > 1 {
		if !isDateValue(rangeValue[0]) {
			// Check if Range is numeric value
			start, _ := strconv.Atoi(rangeValue[0])
			end, _ := strconv.Atoi(rangeValue[1])
			count := 0
			for i := start; i < end; i++ {
				if count == 0 {
					filterWhere[curMarker] += condition + curField + conditionVal
				} else {
					filterWhere[curMarker] += " OR " + curField + conditionVal
				}
				if modPosition == "both" {
					filterArgs[curMarker] = append(filterArgs[curMarker], "%"+strconv.Itoa(i)+"%")
				} else if modPosition == "start" {
					filterArgs[curMarker] = append(filterArgs[curMarker], "%"+strconv.Itoa(i))
				} else if modPosition == "end" {
					filterArgs[curMarker] = append(filterArgs[curMarker], strconv.Itoa(i)+"%")
				} else {
					filterArgs[curMarker] = append(filterArgs[curMarker], strconv.Itoa(i))
				}
				count += 1
			}
		} else if isDateValue(rangeValue[0]) {
			// Check if Range is date value
			var err error
			start, err := time.Parse("01/02/2006", splitValue[0])
			if err != nil {
				fmt.Fprintln(os.Stderr, "err3", err)
				log.Fatal(err)
			}
			end, err := time.Parse("01/02/2006", splitValue[1])
			count := 0
			if err == nil {
				for d := start; !d.After(end); d = d.AddDate(0, 0, 1) {
					if count == 0 {
						filterWhere[curMarker] += condition + curField + conditionVal
					} else {
						filterWhere[curMarker] += " OR " + curField + conditionVal
					}
					if modPosition == "both" {
						filterArgs[curMarker] = append(filterArgs[curMarker], "%"+d.Format("2006-01-02")+"%")
					} else if modPosition == "start" {
						filterArgs[curMarker] = append(filterArgs[curMarker], "%"+d.Format("2006-01-02"))
					} else if modPosition == "end" {
						filterArgs[curMarker] = append(filterArgs[curMarker], d.Format("2006-01-02")+"%")
					} else {
						filterArgs[curMarker] = append(filterArgs[curMarker], d.Format("2006-01-02"))
					}
					count += 1
				}
			} else {
				fmt.Fprintln(os.Stderr, "err4", err)
				log.Fatal(err)
			}
		} else {
			filterWhere[curMarker] += condition + curField + conditionVal
			// filterArgs[curMarker] = append(filterArgs[curMarker], "%"+curFieldValue+"%")
			filterArgs[curMarker] = append(filterArgs[curMarker], curFieldValue)
		}
	} else if len(inValues) > 1 {
		noOfValues := ""
		for i := 0; i < len(inValues); i++ {
			noOfValues += "?,"
			filterArgs[curMarker] = append(filterArgs[curMarker], inValues[i])
		}
		noOfValues = noOfValues[:len(noOfValues)-1]
		filterWhere[curMarker] += condition + curField + " IN (" + noOfValues + ")"
	} else {
		filterWhere[curMarker] += condition + curField + conditionVal
		// filterArgs[curMarker] = append(filterArgs[curMarker], "%"+curFieldValue+"%")
		filterArgs[curMarker] = append(filterArgs[curMarker], curFieldValue)
	}
}

// get Transfers [parents or children of a row]
func GetTransfersData(ctx context.Context, request events.APIGatewayProxyRequest) (events.APIGatewayProxyResponse, error) {

	start := time.Now()
	reqBody := request.Body
	post := map[string]interface{}{}

	temp, req_err := base64.StdEncoding.DecodeString(reqBody)
	if req_err != nil {
		log.Fatal(req_err)
	}
	decodedValue, _ := url.QueryUnescape(string(temp))
	err := json.Unmarshal([]byte(strings.Split(decodedValue, "=")[1]), &post)
	if err != nil {
		fmt.Fprintln(os.Stderr, "err5", err)
		log.Fatal(err)
	}

	//Generate parent data
	Cfg, _ := post["Cfg"].(map[string]interface{})
	Filters, _ := post["Filters"].([]interface{})
	allFilters, _ := Filters[0].(map[string]interface{})
	rowsCount, _ := GetTransfersCount(Cfg, allFilters)
	allPages := math.Ceil(float64(rowsCount) / float64(pageSize))

	// set this to allow Ajax requests from other origins
	elapsed := time.Since(start)
	headers := map[string]string{
		"Access-Control-Allow-Origin": "*", "Access-Control-Allow-Headers": "Origin, X-Requested-With, Content-Type, Accept",
		"Content-type": "application/json",
		"Tcalc":        fmt.Sprintf("%d ms", elapsed.Milliseconds())}

	response, _ := json.Marshal((map[string]interface{}{
		"Body": []string{`#@@@` + fmt.Sprintf("%v", allPages)},
	}))

	return events.APIGatewayProxyResponse{StatusCode: 200, Headers: headers, Body: string(response), IsBase64Encoded: false}, nil
}

func GetTransfersCount(Cfg map[string]interface{}, Filters map[string]interface{}) (int, error) {
	// Grouping
	var GroupCols = Cfg["GroupCols"].(string)
	GroupColsParts := strings.Split(GroupCols, ",")
	var curGroupField = GroupColsParts[0]
	for key, el := range fieldAliases {
		curGroupField = strings.Replace(curGroupField, key, el, 1)
	}

	// Filter process
	var curField, curFieldValue, curOperation, curMarker string
	filterWhere["parent"] = ""
	filterWhere["child"] = ""
	filterArgs["parent"] = nil
	filterArgs["child"] = nil

	for key, el := range Filters {
		if key == "id" {
			continue
		}

		// Check if cur field is child's or parent's and generate preWhere and postWhere correspondingly
		if ItemsFields[key] != "" {
			curMarker = "child"
		} else {
			// cur column is a parent column
			curMarker = "parent"
		}

		if !strings.Contains(key, "Filter") {
			curField = key
			curOperation = Filters[curField+"Filter"].(string)
			curFieldValue = el.(string)
			if ItemsFields[key] != "" {
				curField = ItemsFields[key]
			}

			if fieldAliases[key] != "" {
				curField = fieldAliases[key]
				if curField[:11] == "STR_TO_DATE" {
					curFieldValue = "STR_TO_DATE('" + el.(string) + "','%m/%d/%Y')"
					filterWhere[curMarker] += " AND " + curField + fieldAliasesDate[curOperation] + curFieldValue
					continue
				}
			}

			if curOperation != "" {
				switch curOperation {
				case "1":
					prepareFilterbyDeli(curMarker, curField, curFieldValue, filterWhere, filterArgs, " AND ", " = ? ", "")
				case "2":
					prepareFilterbyDeli(curMarker, curField, curFieldValue, filterWhere, filterArgs, " AND ", " != ? ", "")
				case "3":
					prepareFilterbyDeli(curMarker, curField, curFieldValue, filterWhere, filterArgs, " AND ", " < ? ", "")
				case "4":
					prepareFilterbyDeli(curMarker, curField, curFieldValue, filterWhere, filterArgs, " AND ", " <= ? ", "")
				case "5":
					prepareFilterbyDeli(curMarker, curField, curFieldValue, filterWhere, filterArgs, " AND ", " > ? ", "")
				case "6":
					prepareFilterbyDeli(curMarker, curField, curFieldValue, filterWhere, filterArgs, " AND ", " >= ? ", "")
				case "7":
					prepareFilterbyDeli(curMarker, curField, curFieldValue, filterWhere, filterArgs, " AND ", " LIKE ? ", "end")
				case "8":
					prepareFilterbyDeli(curMarker, curField, curFieldValue, filterWhere, filterArgs, " AND ", " NOT LIKE ? ", "end")
				case "9":
					prepareFilterbyDeli(curMarker, curField, curFieldValue, filterWhere, filterArgs, " AND ", " LIKE ? ", "start")
				case "10":
					prepareFilterbyDeli(curMarker, curField, curFieldValue, filterWhere, filterArgs, " AND ", " NOT LIKE ? ", "start")
				case "11":
					prepareFilterbyDeli(curMarker, curField, curFieldValue, filterWhere, filterArgs, " AND ", " LIKE ? ", "both")
				case "12":
					prepareFilterbyDeli(curMarker, curField, curFieldValue, filterWhere, filterArgs, " AND ", " NOT LIKE ? ", "both")
				case "13":
					prepareFilterbyDeli(curMarker, curField, curFieldValue, filterWhere, filterArgs, " AND ", " IN ? ", "")
				}
			}
		}
	}
	var query = ""
	// Group By clause
	var groupBy string
	if curGroupField != "" {
		groupBy = " GROUP BY " + curGroupField
	}

	if In_array(GroupColsParts[0], GLOBALS["transfer_items_fields"]) {

		if filterWhere["parent"] != "" {
			filterWhere["parent"] = " AND transfers_items.Parent IN (SELECT transfers.id from transfers " + parentQueryJoin + dummyWhere + filterWhere["parent"] + ") "
		}
		query = "SELECT COUNT(transfers_items.id) as rowCount FROM transfers_items INNER JOIN items ON transfers_items.item_uuid = items.id  INNER JOIN units ON transfers_items.item_unit_uuid = units.id INNER JOIN item_types ON items.type_uuid = item_types.id  where 1=1 " + filterWhere["child"] + filterWhere["parent"] + groupBy
	} else {

		if filterWhere["child"] != "" {
			filterWhere["child"] = " AND transfers.id IN (SELECT transfers_items.Parent from transfers_items " + childQueryJoin + dummyWhere + filterWhere["child"] + ") "
		}

		query = "SELECT COUNT(transfers.id) as rowCount FROM transfers INNER JOIN documents ON transfers.document_type_uuid = documents.id  INNER JOIN stores ON transfers.store_origin_uuid = stores.id INNER JOIN stores  ss ON transfers.store_destination_uuid = ss.id  INNER JOIN warehouses ON transfers.warehouse_origin_uuid = warehouses.id INNER JOIN warehouses w2 ON transfers.warehouse_destination_uuid = w2.id  INNER JOIN responsibility_center ON transfers.responsibility_center_uuid = responsibility_center.id where 1=1 " + filterWhere["child"] + filterWhere["parent"] + groupBy
	}

	mergedArgs := MergeMaps(filterArgs["child"], filterArgs["parent"])

	rows, err := db.Query(query, mergedArgs...)
	if err != nil {
		fmt.Fprintln(os.Stderr, "err6", err)
		log.Fatalln(err)
	}

	return CheckCount(rows), nil
}

func MergeMaps(maps ...[]interface{}) (result []interface{}) {
	for _, m := range maps {
		result = append(result, m...)
	}
	return result
}

func CheckCount(rows *sql.Rows) (rowCount int) {
	for rows.Next() {
		err := rows.Scan(&rowCount)
		if err != nil {
			fmt.Fprintln(os.Stderr, "err7", err)
			log.Fatalln(err)
		}
	}
	return rowCount
}

func In_array(needle interface{}, hystack interface{}) bool {
	switch key := needle.(type) {
	case string:
		for _, item := range hystack.([]string) {
			if key == item {
				return true
			}
		}
	case int:
		for _, item := range hystack.([]int) {
			if key == item {
				return true
			}
		}
	case int64:
		for _, item := range hystack.([]int64) {
			if key == item {
				return true
			}
		}
	default:
		return false
	}
	return false
}
