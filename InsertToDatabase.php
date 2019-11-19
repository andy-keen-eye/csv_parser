<?php

/*
* Класс отвечающий за вставку данных в базу данных.
*
*
*/
class InsertToDatabase
{
    /**
     * Insert data in database
     *
     * @param obj $db_connection, array $all_records_array, str $table
     *
     * @return obj
     */
    public static function insertDataWithBrandName($db_connection, $all_records_array, $table)
    {
        $prepared_values = self::prepareData($all_records_array);

        //prepare sql-statement
        $column_names = "reg_number,ipn,person,birthday,reg_addr_koatuu,operation_name,registration_day,brand,model,
            make_year,color,kind,body_type,fuel,capacity,own_weight,brand_model";

        $sql = "INSERT INTO ".$table." (".$column_names.") VALUES ".$prepared_values;
        $pdo = $db_connection->prepare($sql);
        //execute sql-statement
        $pdo_result = $pdo->execute();
        return $pdo_result;
    }

    /*
    * Для каждой записи, представленной массивом $row, данные массива составляются в строку. Все строки соединяются
    * в единую строку, которая будет использована как строка sql-запроса.
    * @param array
    * @return str
    */
    public static function prepareData($data_array)
    {
        $val_str = "";
        foreach ($data_array as $row) {
            $val_str .= "(";

            foreach ($row as $key => $val) {
                switch (gettype($val)) {
                    case ('string'):
                        $val = str_replace ("'", "\'", $val);
                        $val_str .= "'".$val."',";
                        break;
                    case ('integer'):
                        $val_str .= $val.",";
                        break;
                    case ('boolean'):
                        if ($val) $val_str .= '1'.",";
                        else $val_str .= '0'.",";
                        break;
                    case ('null'):
                        $val_str .= "'',";
                        break;
                }
            }
            $val_str = rtrim($val_str, ",");
            $val_str .= "),";
        }
        $val_str = rtrim($val_str, ",");

        return $val_str;
    }
}