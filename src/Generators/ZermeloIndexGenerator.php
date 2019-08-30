<?php
/*
	This is the new Zermelo index, which is better in every way than the current mustache index

*/
namespace CareSet\DURC\Generators;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\DB;
use CareSet\DURC\DURC;
use CareSet\DURC\Signature;

class ZermeloIndexGenerator extends \CareSet\DURC\DURCGenerator {


        //Run only once at the end of generation
        public static function finish(
							$db_config,
                                                        $squash,
                                                        $URLroot){
                //does nothing need to comply with abstract class
        }        



        public static function start(
							$db_config,
                                                        $squash,
                                                        $URLroot){
	}//end start

        public static function run_generator($class_name,$database,$table,$fields,$has_many = null,$has_one = null, $belongs_to = null, $many_many = null, $many_through = null, $squash = false, $URLroot = '/DURC/',$create_table_sql){

		$is_debug = true;

		if($is_debug){
			$structure_comment = "/*\n";
			$structure_comment .= "//fields:\n";
			$structure_comment .= var_export($fields,true);
			$structure_comment .= "//has_many\n";
			$structure_comment .= var_export($has_many,true);
			$structure_comment .= "//has_one\n";
			$structure_comment .= var_export($has_one,true);
			$structure_comment .= "//belongs_to\n";
			$structure_comment .= var_export($belongs_to,true);
			$structure_comment .= "//many_many\n";
			$structure_comment .= var_export($many_many,true);
			$structure_comment .= "//many_through\n";
			$structure_comment .= var_export($many_through,true);
			$structure_comment .= "/*\n";	
		}else{
			$structure_comment = '';
		}

		$report_class_name = "DURC_$class_name";

	
		$report_php_code = "<?php
namespace App\Reports;
use CareSet\Zermelo\Reports\Tabular\AbstractTabularReport;

class $report_class_name extends AbstractTabularReport
{

    //returns the name of the report
    public function GetReportName(): string {
        \$report_name = \"$class_name Report\";
        return(\$report_name);
    }

    //returns the description of the report. HTML is allowed here.
    public function GetReportDescription(): ?string {
        \$desc = \"View the $class_name data
			<br>
			<a href='/DURC/$class_name/create'>Add new $class_name</a>
\";
        return(\$desc);
    }

    //  returns the SQL for the report.  This is the workhorse of the report.
    public function GetSQL()
    {

        \$index = \$this->getCode();

        if(is_null(\$index)){

                \$sql = \"
SELECT * FROM $database.$table
\";

        }else{

                \$sql = \"
SELECT * FROM $database.$table WHERE id = \$index
\";

        }

        \$is_debug = false;
        if(\$is_debug){
                echo \"<pre>\$sql\";
                exit();
        }

        return \$sql;
    }

    //decorate the results of the query with useful results
    public function MapRow(array \$row, int \$row_number) :array
    {

        extract(\$row);

        //link this row to its DURC editor
        \$row['id'] = \"<a href='/DURC/$class_name/\$id'>\$id</a>\";

        return \$row;
    }

    //see Zermelo documentation to understand following functions:
    //https://github.com/CareSet/Zermelo

    public \$NUMBER     = ['ROWS','AVG','LENGTH','DATA_FREE'];
    public \$CURRENCY = [];
    public \$SUGGEST_NO_SUMMARY = ['ID'];
    public \$REPORT_VIEW = null;

    public function OverrideHeader(array &\$format, array &\$tags): void
    {
    }

    public function GetIndexSQL(): ?array {
                return(null);
    }

        //turns on the cache, should be off for development and small databases or simple queries
   public function isCacheEnabled(){
        return(false);
   }

        //only matters if the cache is on
   public function howLongToCacheInSeconds(){
        return(1200); //twenty minutes by default
   }

$structure_comment

}
?>";
	
	$signed_zermelo_report = Signature::sign_phpfile_string($report_php_code);

	$report_path = base_path().'/app/Reports/'; //how do we support alternate paths??
	$report_file_location = $report_path . "$report_class_name.php";
	
	if(file_exists($report_file_location)){
		//do nothing for now..
                        $current_file_contents = file_get_contents($report_file_location);
                        $has_file_changed = Signature::has_signed_file_changed($current_file_contents);
                        if($has_file_changed){
                                //if a signed file has changed, we never never overwrite it.
                                //it contains manually created code that needs to be protected.
                                //so we do nothing here...
                                echo "Not overwriting $report_file_location it has manual modifications\n";
                        }else{
                                //if the file has not been modified, then it is still in its "autogenerated" state.
                                //files in that state are always overridden by newer autogenerated files..
				file_put_contents($report_file_location,$signed_zermelo_report);
                        }

	}else{
		//this is initial file creation
		file_put_contents($report_file_location,$signed_zermelo_report);

	}


	}//end generate function




}//end class
