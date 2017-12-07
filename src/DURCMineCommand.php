<?php
/*
	This is the place where the actual command is orchestrated.
	it ends up being our "main()"


*/
namespace CareSet\DURC;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class DURCMineCommand extends Command{

    protected $signature = 'DURC:mine {--squash} {--DB=*}';
    protected $description = 'DURC:mine generates a json representation of your database structure and relationships, by mining your DB directly.';

    public function handle(){
	//what does this do?

	$databases = $this->option('DB');

	$squash = $this->option('squash');

	$db_struct = DURC::getDBStruct($databases);

	//do a pass to get all of the potential table targets

	$ignored_tables = [];

	$new_struct = [];
	$table_namespace = [];
	foreach($db_struct as $db => $table_list){
		foreach($table_list as $table_name => $column_data){
			
			$table_tag = strtolower($table_name); //lets use lowercase for linking purposes

			if(isset($table_namespace[$table_name])){	//shit
				$ignored_tables[$table_tag] = "This table name is used twice...";
			}else{
				$table_namespace[$table_name]  = $table_name; //use to prevent name collisions which are on the table level, not the db.table level..
				$new_struct[$db][strtolower($table_name)] = [
						'table_name' => $table_name,
						'db' => $db,
						'column_data' => $column_data,
					];
			}
		}
	}

	//in the second pass we look for links to those tables...

	//and start the second pass..
	foreach($db_struct as $db => $table_list){
		foreach($table_list as $table_name => $column_data){
			foreach($column_data as $column_name => $column_data){
				$column_name = $column_data['column_name'];
				$data_type = $column_data['data_type'];
				//we do not care about anything that does not have an '_id' at the end
				$last_three = substr($column_name,-3);
				if(strtolower($last_three) == '_id'){
			                $col_array = explode('_', $column_name);

					//imagine the three cases:
					// #1  something_id
					// #2 cool_something_id
					// #3 really_very_cool_something_id

        	       	 		$throw_away_the_id = array_pop($col_array); // we don't need _id...
                			$other_table_tag = strtolower(array_pop($col_array)); //this should always result in 'something' 
					//see if there is more to the column... enouigh to detail a relationship..
                			$relationship = implode('_', $col_array); //this handles both 'cool' and 'really_cool_something'
                			if (strlen($relationship) == 0) {
                    				$relationship = null; //which means it the same as 'something_something_id'
                			}

					$my_object_name = strtolower($table_name);

					//but if 'something' is not the name of another table.. there is no other table to link to. 
					if(isset($new_struct[$db][strtolower($other_table_tag)])){
						//then this table exists as a target..

						//Has Many Calculation
                    				$has_many_tmp = [
                            				'prefix' => $relationship,
                            				'type'   => $my_object_name,
							'from' => 'has_many_tmp',
                            			];
						if(is_null($relationship)){
							$has_many_key = $my_object_name;
						}else{
                    					$has_many_key = $relationship.'_'.$my_object_name;
						}
                    				$new_struct[$db][strtolower($other_table_tag)]['has_many'][$has_many_key] = $has_many_tmp;



						//Belongs To Calculation
                    				$belongs_to_tmp = [
                            				'prefix' => $relationship,
                            				'type'   => $other_table_tag,
							'from' => 'belongs_to_tmp',
                            			];
						if(is_null($relationship)){
							$belongs_to_key = $other_table_tag;
						}else{
							$belongs_to_key = $relationship.'_'.$other_table_tag;
						}
                    				$new_struct[$db][strtolower($my_object_name)]['belongs_to'][$belongs_to_key] = $belongs_to_tmp;

					}


				}else{
					//this column is not an _id and this process does not care about it.
				}
			}// end loop over columns for this table..
		}//end loop over each table in table_list
	}//end loop over every db in db_struct (to find links between tables)


	//now merge db_struct and new_struct...


	//later we will support the notion of 'many_to_many' through pivot tables...
	
	//later we MAY support the notion of 'has_many_through' but that could create a rats nest...

	DURC::writeDURCDesignConfigJSON($new_struct,$squash);	

    }


}
