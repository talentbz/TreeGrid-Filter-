package main

import (
	"context"
	"database/sql"
	"encoding/base64"
	"encoding/json"
	"fmt"
	"log"
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
var parentBuild = "SELECT * FROM transfers WHERE Parent = ''"

var parentQuery = "SELECT transfers.id,  transfers.document_no, transfers.document_date,  transfers.posting_date, transfers.entry_date,  transfers.delivery_date, documents.document_type AS document_type,  documents.document_abbrevation AS document_abbrevation, stores.code AS store_origin_code, warehouses.code AS warehouse_origin_code, warehouses.code AS warehouse_destination_code, ss.code AS store_destination_code,  responsibility_center.code AS responsibility_center, transfers.document_type_uuid, transfers.store_origin_uuid, transfers.warehouse_origin_uuid, transfers.warehouse_destination_uuid,  transfers.responsibility_center_uuid, transfers.warehouseman_destination_approve, transfers.has_child FROM transfers INNER JOIN documents ON transfers.document_type_uuid = documents.id  INNER JOIN stores ON transfers.store_origin_uuid = stores.id INNER JOIN stores  ss ON transfers.store_destination_uuid = ss.id  INNER JOIN warehouses ON transfers.warehouse_origin_uuid = warehouses.id INNER JOIN warehouses w2 ON transfers.warehouse_destination_uuid = w2.id  INNER JOIN responsibility_center ON transfers.responsibility_center_uuid = responsibility_center.id where 1=1 "

var parentQueryJoin = "INNER JOIN documents ON transfers.document_type_uuid = documents.id  INNER JOIN stores ON transfers.store_origin_uuid = stores.id INNER JOIN stores  ss ON transfers.store_destination_uuid = ss.id  INNER JOIN warehouses ON transfers.warehouse_origin_uuid = warehouses.id INNER JOIN warehouses w2 ON transfers.warehouse_destination_uuid = w2.id  INNER JOIN responsibility_center ON transfers.responsibility_center_uuid = responsibility_center.id "

// Dummy where clause used before AND clauses
var dummyWhere = " where 1=1 "

var childQuery = " SELECT transfers_items.id, transfers_items.Parent, item_types.code AS item_type, items.no AS item_no, items.description AS item_name, units.code AS item_unit, transfers_items.input_quantity, transfers_items.item_quantity_unit, transfers_items.item_quantity, transfers_items.item_tempory, transfers_items.item_uuid, transfers_items.item_unit_uuid  FROM transfers_items INNER JOIN items ON transfers_items.item_uuid = items.id  INNER JOIN units ON transfers_items.item_unit_uuid = units.id INNER JOIN item_types ON items.type_uuid = item_types.id "

var childQueryJoin = " INNER JOIN items ON transfers_items.item_uuid = items.id  INNER JOIN units ON transfers_items.item_unit_uuid = units.id INNER JOIN item_types ON items.type_uuid = item_types.id "

func init() {
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
	fieldAliases["warehouse_origin_code"] = "warehouses.code"
	fieldAliases["warehouse_destination_code"] = "warehouses.code"
	fieldAliases["store_destination_code"] = "stores.code"
	fieldAliases["responsibility_center"] = " responsibility_center.code"
	fieldAliases["document_date"] = "STR_TO_DATE(document_date,'%m/%d/%Y')"
	fieldAliases["posting_date"] = "STR_TO_DATE(posting_date,'%m/%d/%Y')"
	fieldAliases["delivery_date"] = "STR_TO_DATE(delivery_date,'%m/%d/%Y')"
	fieldAliases["entry_date"] = "STR_TO_DATE(entry_date,'%m/%d/%Y')"

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
	initDb()
	lambda.Start(GetTransfersPage)
}

func initDb() {
	var err error
	dbconf, err := os.ReadFile("./config/db-local.conf")
	if err != nil {
		fmt.Fprintln(os.Stderr, "err1", err)
		log.Fatalln(err)
	}

	db, err = sql.Open("mysql", string(dbconf))
	if err != nil {
		fmt.Fprintln(os.Stderr, "err2", err)
		log.Fatalln(err)
	}
}

// get Transfers [parents or children of a row]
func GetTransfersPage(ctx context.Context, request events.APIGatewayProxyRequest) (events.APIGatewayProxyResponse, error) {

	start := time.Now()
	reqBody := request.Body
	post := map[string]interface{}{}

	temp, req_err := base64.StdEncoding.DecodeString(reqBody)
	if req_err != nil {
		log.Fatalln(req_err)
	}
	decodedValue, _ := url.QueryUnescape(string(temp))
	fmt.Fprintln(os.Stderr, "decodedValue", decodedValue)
	fmt.Fprintln(os.Stderr, "Split.decodedValue", strings.Split(decodedValue, "="))
	fmt.Fprintln(os.Stderr, "substr", decodedValue[strings.Index(decodedValue, "=")+1:])

	err := json.Unmarshal([]byte(decodedValue[strings.Index(decodedValue, "=")+1:]), &post)
	if err != nil {
		fmt.Fprintln(os.Stderr, "err3", err)
		log.Fatalln(err)
	}

	//Generate parent data
	Cfg, _ := post["Cfg"].(map[string]interface{})
	Filters, _ := post["Filters"].([]interface{})
	postBody, _ := post["Body"].([]interface{})
	allFilters, _ := Filters[0].(map[string]interface{})

	cs := 0
	childRows := ""
	for _, pos := range postBody {
		if val, ok := pos.(map[string]interface{})["id"].(string); ok {
			cs, _ = strconv.Atoi(val)
		}
		if val, ok := pos.(map[string]interface{})["Rows"].(string); ok {
			childRows = val
		}
	}

	var response = []map[string]interface{}{}
	if cs != 0 {
		allFilters, _ = postBody[0].(map[string]interface{})
		// Get data with filters
		response, _ = GetTransfersPageData(Cfg, allFilters, childRows, true)
	} else {
		// Get data without filters
		response, _ = GetTransfersPageData(Cfg, allFilters, childRows, false)
	}

	addData := [][]map[string]interface{}{}
	addData = append(addData, response)

	result, _ := json.Marshal(map[string][][]map[string]interface{}{
		"Body": addData,
	})

	// set this to allow Ajax requests from other origins
	elapsed := time.Since(start)
	headers := map[string]string{
		"Access-Control-Allow-Origin": "*", "Access-Control-Allow-Headers": "Origin, X-Requested-With, Content-Type, Accept",
		"Content-type": "application/json",
		"Tcalc":        fmt.Sprintf("%d ms", elapsed.Milliseconds())}

	return events.APIGatewayProxyResponse{StatusCode: 200, Headers: headers, Body: string(result), IsBase64Encoded: false}, nil
}

// Check the given string is date or Not.
func isDateValue(stringDate string) bool {
	_, err := time.Parse("01/02/2006", stringDate)
	fmt.Fprintln(os.Stderr, "err4", err)
	return err == nil
}

// Prepare filters based on the filter params.
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
				fmt.Fprintln(os.Stderr, "err5", err)
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
				fmt.Fprintln(os.Stderr, "err6", err)
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

// Get all the data based on grouping, filters.
func GetTransfersPageData(Cfg map[string]interface{}, Filters map[string]interface{}, childRows string, filterApplied bool) ([]map[string]interface{}, error) {
	var curGroupField, curField, curOperation, curFieldValue, curMarker, sort_query, child_sort_query string
	var groupBy, query string
	var resultData = make([]map[string]interface{}, 0)
	// Grouping
	var GroupCols = Cfg["GroupCols"].(string)
	GroupColsParts := strings.Split(GroupCols, ",")
	if GroupColsParts[0] != "" {
		curGroupField = GroupColsParts[0]
		for key, el := range fieldAliases {
			curGroupField = strings.Replace(curGroupField, key, el, 1) // check this line group by: store_destination_code & responsibility_center
		}
	}

	// Sorting
	if Cfg["SortCols"].(string) != "" {
		sort_query, child_sort_query = construct_sort_query(Cfg["SortCols"].(string), Cfg["SortTypes"].(string))
	}

	// Filter process
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
	// Group By clause
	if curGroupField != "" {
		groupBy = " GROUP BY " + curGroupField
	}
	mergedArgs := MergeMaps(filterArgs["child"], filterArgs["parent"])

	if curGroupField != "" {
		if filterWhere["parent"] != "" {
			parentBuild += " " + filterWhere["parent"]
		}
		innerQuery := ""
		if filterWhere["child"] != "" {
			innerQuery = "SELECT Parent FROM transfers_items WHERE DEF='Data' " + filterWhere["child"] + child_sort_query
		}
		if innerQuery != "" {
			parentBuild = parentBuild + " AND grid_id IN (" + innerQuery + ") "
		}
		mergedData := [1]string{}
		if len(mergedArgs) == 0 {
			mergedData[0] = ""
		}
		data := make([]string, len(mergedData))
		for i, v := range mergedData {
			data[i] = fmt.Sprint(v)
		}
		data = append(data, "0", "100")
		extracted_where_clause := extract_where_clause(parentBuild, data)
		if childRows != "" {
			child_level, _ := strconv.Atoi(string(childRows[0]))
			child_rows := childRows[1:]
			resultData = GetAllData(child_level, child_rows, GroupColsParts, 0)
		} else {
			resultData = GetAllData(0, extracted_where_clause, GroupColsParts, 100)
		}
		return resultData, nil
	} else {
		if !filterApplied {

			if filterWhere["parent"] != "" {
				filterWhere["parent"] = " AND transfers.id IN (SELECT transfers.id from transfers " + parentQueryJoin + dummyWhere + filterWhere["parent"] + ") "
			}
			query = parentQuery + filterWhere["child"] + filterWhere["parent"] + groupBy + sort_query
		} else {
			getChild := ""

			if filterWhere["child"] != "" {
				filterWhere["child"] = " AND transfers.id IN (SELECT transfers_items.Parent from transfers_items " + childQueryJoin + dummyWhere + filterWhere["child"] + ") "
			} else {

				for key, el := range Filters {
					if key == "id" {
						getChild = "where parent IN (" + el.(string) + ")"
					}
				}

			}
			query = childQuery + filterWhere["child"] + filterWhere["parent"] + getChild + child_sort_query
		}
	}
	return getJSON(query, mergedArgs, filterApplied), nil
}

// Prepare and Execute the query and return the results as JSON.
func getJSON(sqlString string, mergedArgs []interface{}, filterApplied bool) []map[string]interface{} {
	stmt, err := db.Prepare(sqlString)
	if err != nil {
		fmt.Fprintln(os.Stderr, "err7", err)
		log.Fatal(err)
	}
	defer stmt.Close()

	rows, err := stmt.Query(mergedArgs...)
	if err != nil {
		fmt.Fprintln(os.Stderr, "err8", err)
		log.Fatal(err)
	}
	defer rows.Close()

	columns, err := rows.Columns()
	if err != nil {
		fmt.Fprintln(os.Stderr, "err9", err)
		log.Fatal(err)
	}

	tableData := make([]map[string]interface{}, 0)

	count := len(columns)
	values := make([]interface{}, count)
	scanArgs := make([]interface{}, count)
	for i := range values {
		scanArgs[i] = &values[i]
	}

	for rows.Next() {
		err := rows.Scan(scanArgs...)
		if err != nil {
			fmt.Fprintln(os.Stderr, "err10", err)
			log.Fatal(err)
		}

		entry := make(map[string]interface{})
		for i, col := range columns {
			v := values[i]

			b, ok := v.([]byte)
			if ok {
				entry[col] = string(b)
			} else {
				entry[col] = v
			}
		}
		if !filterApplied {
			entry["Expanded"] = "0"
			entry["Count"] = "2"
		}
		tableData = append(tableData, entry)
	}

	return tableData
}

// Merge the parent and child filters.
func MergeMaps(maps ...[]interface{}) (result []interface{}) {
	for _, m := range maps {
		result = append(result, m...)
	}
	return result
}

// Check whether the element is in the array or not.
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

// Construct the parent and child sort query using Sort params.
func construct_sort_query(sort_params string, sort_type_params string) (string, string) {
	// Constructing the sorting part of the query
	sort_cols := strings.Split(sort_params, ",")
	sort_types := strings.Split(sort_type_params, ",")
	sort_query := ""
	child_sort_query := ""

	for i := 0; i < len(sort_cols); i++ {
		if In_array(sort_cols[i], GLOBALS["transfer_items_fields"]) {
			if child_sort_query != "" {
				child_sort_query += ", "
			}
			child_sort_query += sort_cols[i]
			if sort_types[i] == "1" {
				child_sort_query += " DESC"
			} else {
				child_sort_query += " ASC"
			}
		} else {
			if sort_query != "" {
				sort_query += ", "
			}
			sort_query += sort_cols[i]
			if sort_types[i] == "1" {
				sort_query += " DESC"
			} else {
				sort_query += " ASC"
			}
		}
	}

	if sort_query != "" {
		sort_query = " ORDER BY " + sort_query + " "
	}
	if child_sort_query != "" {
		child_sort_query = " ORDER BY " + child_sort_query + " "
	}

	return sort_query, child_sort_query
}

// Extract the where clause from the query
func extract_where_clause(query string, params []string) string {
	param_value_count := strings.Count(query, "?")
	for count := 0; count < param_value_count; count++ {
		query = strings.Replace(query, "?", "'"+params[count]+"'", 1)
	}

	pos := strings.Index(query, "WHERE")

	return query[pos:]
}

// Fetch and return all the data based on filters and grouping.
func GetAllData(level int, where string, group_cols []string, start int) []map[string]interface{} {
	tableData := make([]map[string]interface{}, 0)
	levels := len(group_cols)
	var name, Rows string

	var rows *sql.Rows

	// If both the level are equal then return the row
	if level == levels {
		temp_query := "SELECT * FROM transfers " + where
		parentRow, err := db.Query(temp_query)
		if err != nil {
			fmt.Fprintln(os.Stderr, "err11", err)
			panic(err)
		}
		rows = parentRow
	} else {
		name = group_cols[level]
		if In_array(name, GLOBALS["transfer_items_fields"]) {
			var inner_child_conditions, updated_where, whereCondition string
			if strings.Index(where, " AND grid_id IN ( SELECT Parent FROM transfers_items WHERE ") > 0 {
				inner_child_conditions = get_string_between(where, " AND grid_id IN ( SELECT Parent FROM transfers_items WHERE ", ")")
				sub_string := get_string_between(where, " AND grid_id IN (", ")")
				updated_where = strings.Replace(where, sub_string, "", 1)
				updated_where = strings.Replace(updated_where, "grid_id IN ()", "1", 1)
			}
			if inner_child_conditions != "" {
				inner_child_conditions = " AND " + inner_child_conditions
			}
			if updated_where != "" {
				whereCondition = updated_where
			} else {
				whereCondition = where
			}
			parent_table_filter_query := "SELECT grid_id FROM transfers " + (whereCondition)
			temp_query := "SELECT DISTINCT " + name + " FROM transfers_items WHERE Parent in ( " + parent_table_filter_query + " )" + inner_child_conditions
			parentRow, err := db.Query(temp_query)
			if err != nil {
				fmt.Fprintln(os.Stderr, "err12", err)
				panic(err)
			}
			rows = parentRow
		} else {
			query := "SELECT DISTINCT " + name + " FROM transfers " + where
			parentRow, err := db.Query(query)
			if err != nil {
				fmt.Fprintln(os.Stderr, "err13", err)
				panic(err)
			}
			rows = parentRow
		}
	}
	// count := 0
	// for rows.Next() {
	// 	count += 1
	// }

	// if level == 0 && start+100 < count {
	// 	count = start + 100 // 100 rows per page on level 0
	// }

	// startRow := 0
	// if level == 0 {
	// 	startRow = start
	// }
	defer rows.Close()
	columns, err := rows.Columns()
	if err != nil {
		fmt.Fprintln(os.Stderr, "err14", err)
		log.Fatal(err)
	}
	// Get the columns count and create the scan object with the column count.
	columnCount := len(columns)
	values := make([]interface{}, columnCount)
	scanArgs := make([]interface{}, columnCount)
	for i := range values {
		scanArgs[i] = &values[i]
	}

	// Loop through the rows and form the row content.
	for rows.Next() {
		tempObj := make(map[string]interface{})
		tempObj["Def"] = "Group"
		tempObj["Count"] = "1"

		err := rows.Scan(scanArgs...)
		if err != nil {
			fmt.Fprintln(os.Stderr, "err15", err)
			log.Fatal(err)
		}
		// Loop through the rows and form the row struct.
		row := make(map[string]interface{})
		for i, col := range columns {
			v := values[i]
			b, ok := v.([]byte)
			if ok {
				row[col] = string(b)
			} else if b != nil {
				row[col] = v
			}
		}

		if level == levels {
			tableData = append(tableData, row)
		} else {
			var value_sums, val, where2 string // sql.nullString
			if value, ok := row[name].(string); ok {
				val = value
			}
			if In_array(name, GLOBALS["transfer_items_fields"]) {
				// If grouped by child column
				if strings.Index(where, " AND "+name+"='") > 0 {
					// If the column is already added in the WHERE clause
					// Just update the query to adjust column value
					where2 = replace_column_value_in_query(where, name, val)
				} else {
					where2 = add_child_condition(where, name, val)
				}
				Rows = strconv.Itoa(level+1) + where2 // Builds new attribute Rows for identification
			} else {
				where2 = " AND " + name + "='" + val + "'"
				Rows = strconv.Itoa(level+1) + where + where2 // Builds new attribute Rows for identification
			}
			tempObj["document_type"] = val

			if In_array(name, GLOBALS["transfer_items_fields"]) {
				// Query to get aggregated sum for "item_quantity" data for each transfer_items group.
				calculations_query := "SELECT COALESCE(sum(item_quantity),'') as value_sums FROM (SELECT item_quantity FROM transfers_items WHERE Parent in (SELECT grid_id FROM transfers " + where + ") AND " + name + "='" + val + "') AS temp;"
				calculations_rs, err := db.Query(calculations_query)
				if err != nil {
					fmt.Fprintln(os.Stderr, "err16", err)
					panic(err)
				}
				for calculations_rs.Next() {
					err = calculations_rs.Scan(&value_sums)
					if err != nil {
						fmt.Fprintln(os.Stderr, "err17", err)
						panic(err)
					}
				}
				// valueSums := calculations_rs->Get("value_sums")
				tempObj["item_quantity"] = value_sums
			} else {
				var min, max, value_sum string
				// Query to get aggregated sum for "warehouse_destination_uuid" data for each group.
				calculations_query := "SELECT COALESCE(sum(warehouseman_destination_approve),'') as value_sum, COALESCE(MIN(document_date), '') AS min, COALESCE(MAX(document_date), '') AS max FROM (SELECT warehouseman_destination_approve, document_date FROM transfers " + where + where2 + ") AS temp"
				calculations_rs, err := db.Query(calculations_query)
				if err != nil {
					fmt.Fprintln(os.Stderr, "err18", err)
					panic(err)
				}
				for calculations_rs.Next() {
					err = calculations_rs.Scan(&min, &max, &value_sum)
					if err != nil {
						fmt.Fprintln(os.Stderr, "err19", err)
						log.Panic(err)
					}
				}
				document_date := ""
				if min != "" && max != "" {
					document_date = min + "~" + max
				}
				tempObj["document_date"] = document_date
				tempObj["warehouse_destination_uuid"] = value_sum
			}
			tempObj["Rows"] = Rows
			tableData = append(tableData, tempObj)
		}
	}
	return tableData
}

// Returns the substring between two substring (start, end), within one main string(string)
func get_string_between(str string, start string, end string) (result string) {
	ini := strings.Index(str, start)
	if ini == 0 {
		return ""
	}
	ini += len(start)
	endStr := strings.Index(str, end)
	if endStr == 0 {
		return ""
	}
	return str[ini:endStr]
}

// Returns the replaced column in the query.
func replace_column_value_in_query(orignal_query string, col_name string, col_value string) string {
	updated_query := ""
	if strings.Index(orignal_query, " AND "+col_name+"=''") > 0 {
		// If the column value was empty
		updated_query = strings.Replace(orignal_query, " AND "+col_name+"=''", " AND "+col_name+"='"+col_value+"'", 1)
	} else {
		// If the column value was not empty
		sub_string := get_string_between(orignal_query, " AND "+col_name+"='", "'")
		updated_query = strings.Replace(orignal_query, sub_string, col_value, 1)
	}
	return updated_query
}

// Add (WHERE) condition for the child table columns
func add_child_condition(orignal_query string, col_name string, col_value string) string {
	if strings.Index(orignal_query, "grid_id IN ( SELECT Parent FROM transfers_items") > 0 {
		// If already filtered on child column(s)
		orignal_query = strings.Replace(orignal_query, ")", " AND "+col_name+"='"+col_value+"' )", 1)
	} else {
		orignal_query += " AND grid_id IN ( SELECT Parent FROM transfers_items WHERE " + col_name + "='" + col_value + "' ) "
	}
	return orignal_query
}
