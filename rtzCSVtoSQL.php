<?php

require_once('InsertToDatabase.php');

class CSVtoSQLController {

	private $row_per_sql_insert = 3000;
	private $rowCount = 0; //count row in created files
	private $reader; //pointer to resourse file
	private $fileReadingResult = true; // result of reading file
	private $all_records_array = [];
    private $table = 'registration_record';

    /**
	 * Parsing CSV file.
	 *
	 * @param file pointer $resource, obj $db_connection, int $column_amount, int $year, str $file, array $update_year
	 *
	 * @return string
	 */
	public function parse($resource, $db_connection, $column_amount, $year, $file, $update_year)
	{
		if (! file_exists ($resource)) {
			return ('Файл не найден');
		}

		// open file
		if(($this->reader = fopen($resource, 'r')) !== false) {
		    //read headers
		    $data = fgetcsv($this->reader, $delimiter = "\n", $enclosure = "\n");
		    // loop through the resource file line-by-line while file is readed successfully
		    while($this->fileReadingResult == true) {
		    	$this->fileReadingResult = $this->readFileRowByRow($column_amount, $year, $file, $update_year);
		    	if ($this->rowCount >= $this->row_per_sql_insert) {
                    //insert into database
					$insert_result = InsertToDatabase::insertDataWithBrandName($db_connection, $this->all_records_array, $this->table);
					var_dump($insert_result);//exit();
                    $this->all_records_array = [];
					$this->rowCount = 0;
					sleep(5);
		    	}
			}
			//if read file == false and $this->rowCount < $this->row_per_sql_insert
			if (! empty($this->all_records_array)) {
				//insert into database
				InsertToDatabase::insertDataWithBrandName($db_connection, $this->all_records_array, $this->table);
			}
			fclose($this->reader);
			return ('Данные файла обработаны');
		}
		//if fail to open file
		return ('Файл не найден');
	} // func

	/**
	 * Read file row by row
	 *
	 * @param int $column_amount, int $year, str $file, array $update_year
	 *
	 * @return bool
	 */
	private function readFileRowByRow($column_amount, $year, $file, $update_year)
	{
        // if row readed successfully
        if( ($data = fgetcsv($this->reader, 0, $delimiter = "\n", $enclosure = "\n")) !== false ) {
	        $recordArray = $this->strToArray($data[0]);

        	$recordArray = $this->sortInAppropriateOrder($recordArray, $column_amount, $year);
        	if ($recordArray) {
                //*******************************************************************************************************
                //эта проверка используется при обновлении данных за текущий год, чтобы не вносить в базу данные, которые были внесены более ранними обновлениями
                if (
                    ($year === $update_year['year']) &&
                    ($file == $update_year['file']) &&
                    ($recordArray[6] < $update_year['last_updated'])
                    ) {
                    return true;
                }
                $this->all_records_array[] = $recordArray;
                $this->rowCount++;
            }
        	$data = []; //default value
	        return true;
        } else {  // in case of fgetcsv($this->reader) === false
       		return false;
        }
        return false;
	}

    /**
     * Form an array from a csv string
     *
     * @param str $csvStr
     *
     * @return array
     */
    private function strToArray($csvStr)
    {
        $offset = 0;
        $result_array = [];
        do {
            $index = strpos($csvStr, ";", $offset);
            //if only the last substring is left, then the semicolon will not be found
            if ($index === false) {
                $sub_str = substr($csvStr, $offset);
            } else {
                $length = $index - $offset;
                $sub_str = substr($csvStr, $offset, $length);
            }
            if ($sub_str == 'NULL') {
                $sub_str = '';
            }
            //trim " form start and an end of substring
            $result_array[] = trim($sub_str, '"');
            //assign to $offset next index after found semicolon
            $offset = $index + 1;
        } while ($index !== false);
        return $result_array;
    }

	/**
     * Sort array in appropriate order fot inserting to database
     *
     * @param array $recordArray, int $column_amount
     *
     * @return mixed (array or false)
     */
    private function sortInAppropriateOrder($recordArray, $column_amount, $year)
    {
    	if ($column_amount == 21) {
            $brand_name = $this->getBrandNameWithoutModel($recordArray[9], $recordArray[10]);
            //if reg_number exists
            if ($recordArray[20] != "") {
                if ($recordArray[2] == '') {
                    $recordArray[2] = '0001-01-01';
                }
                if ($recordArray[6] == '') {
                    $recordArray[6] = '0001-01-01';
                }
                return [
    	        	0 => $recordArray[20],
    	            1 => $recordArray[0],
    	            2 => $recordArray[1],
    	            3 => $recordArray[2],
    	            4 => $recordArray[3],
    	            5 => $recordArray[5],
    	            6 => $recordArray[6],
    	            7 => $brand_name,
    	            8 => $recordArray[10],
    	            9 => $recordArray[11],
    	            10 => $recordArray[12],
    	            11 => $recordArray[13],
    	            12 => $recordArray[14],
    	            13 => $recordArray[16],
    	            14 => $recordArray[17],
    	            15 => $recordArray[18],
                    16 => $recordArray[9]
    	        ];
            }
    	} elseif ($column_amount == 19) {
        	$brand_name = $this->getBrandNameWithoutModel($recordArray[7], $recordArray[8]);

            // ********************************************************************************
            //for year > 2018 where in $recordArray[7] contains brand without model
            if (($year > 2018) && ($brand_name == '')) {
                $brand_name = $recordArray[7];
            }

            //if reg_number exists
            if ($recordArray[18] !== "") {
                if ($recordArray[4] == '') {
                    $recordArray[4] = '0001-01-01';
                }
                //for translating date into correct format (from d.m.y to Y-m-d)
                $recordArray[4] = strtotime($recordArray[4]);
                $recordArray[4] = date('Y-m-d',$recordArray[4]);
                return [
    	    		0 => $recordArray[18],
    	    		1 => '',
    	            2 => $recordArray[0],
    	            3 => '0001-01-01',
    	            4 => $recordArray[1],
    	            5 => $recordArray[3],
    	            6 => $recordArray[4],
    	            7 => $brand_name,
    	            8 => $recordArray[8],
    	            9 => $recordArray[9],
    	            10 => $recordArray[10],
    	            11 => $recordArray[11],
    	            12 => $recordArray[12],
    	            13 => $recordArray[14],
    	            14 => $recordArray[15],
    	            15 => $recordArray[16],
                    16 => $recordArray[7]
    	        ];
            }
    	}
        return false;
    }

    /**
     * Get the brand name if the brand name contains the model name
     *
     * @param str $brand, $model
     *
     * @return str $brand_name
     */
    private function getBrandNameWithoutModel($brand, $model)
    {
        $index_model = strpos($brand, " ".$model);
        if ($index_model !== false) {
            $brand_name = substr($brand, 0, $index_model);
            $brand_name = trim($brand_name, " ");
        } else {
            return '';
        }
        return $brand_name;
    }
}