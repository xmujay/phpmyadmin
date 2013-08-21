<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for sql_query_form.lib.php
 *
 * @package PhpMyAdmin-test
 */

//the following defination should be used globally
$GLOBALS['server'] = 1;
define('PMA_CSDROPDOWN_CHARSET',   1);
//_SESSION
$_SESSION['relation'][$GLOBALS['server']] = array(
    'table_coords' => "table_name",
    'displaywork' => 'displaywork',
    'db' => "information_schema",
    'table_info' => 'table_info',
    'relwork' => 'relwork',
    'relation' => 'relation',
    'bookmarkwork' => 'bookmarkwork',
);
//$GLOBALS
$GLOBALS['cfg']['Server']['user'] = "user";
$GLOBALS['cfg']['Server']['pmadb'] = "pmadb";
$GLOBALS['cfg']['Server']['bookmarktable'] = "bookmarktable";

/*
 * Include to test.
*/
require_once 'libraries/Util.class.php';
require_once 'libraries/Advisor.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/mysql_charsets.lib.php';
require_once 'libraries/ServerStatusData.class.php';
require_once 'libraries/relation.lib.php';
require_once 'libraries/sql_query_form.lib.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/Message.class.php';
require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/sqlparser.lib.php';
require_once 'libraries/js_escape.lib.php';

/**
 * class PMA_SqlQueryForm_Test
 *
 * this class is for testing sql_query_form.lib.php functions
 *
 * @package PhpMyAdmin-test
 */
class PMA_SqlQueryForm_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Test for setUp
     *
     * @return void
     */
    public function setUp()
    {
        //$GLOBALS
        $GLOBALS['max_upload_size'] = 100;
        $GLOBALS['PMA_PHP_SELF'] = PMA_getenv('PHP_SELF');
        $GLOBALS['db'] = "PMA_db";
        $GLOBALS['table'] = "table";
        $GLOBALS['pmaThemeImage'] = 'image';
        $GLOBALS['table'] = "PMA_table";
        $GLOBALS['text_dir'] = "text_dir";

        $GLOBALS['cfg']['GZipDump'] = false;
        $GLOBALS['cfg']['BZipDump'] = false;
        $GLOBALS['cfg']['ZipDump'] = false;
        $GLOBALS['cfg']['ServerDefault'] = "default";
        $GLOBALS['cfg']['TextareaAutoSelect'] = true;
        $GLOBALS['cfg']['TextareaRows'] = 100;
        $GLOBALS['cfg']['TextareaCols'] = 11;
        $GLOBALS['cfg']['DefaultTabDatabase'] = "default_database";
        $GLOBALS['cfg']['RetainQueryBox'] = true;
        $GLOBALS['cfg']['ActionLinksMode'] = 'both';

        //$_SESSION
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
        $_SESSION['PMA_Theme'] = new PMA_Theme();

        //Mock DBI
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $fetchResult = array("index1"=>"table1", "index2"=>"table2");
        $dbi->expects($this->any())
            ->method('fetchResult')
            ->will($this->returnValue($fetchResult));

        $getColumns = array(
            array(
                "Field" => "filed1",
                "Comment" => "Comment1"
            )
        );
        $dbi->expects($this->any())
            ->method('getColumns')
            ->will($this->returnValue($getColumns));

        $GLOBALS['dbi'] = $dbi;
    }

    /**
     * Test for PMA_getHtmlForSqlQueryFormUpload
     *
     * @return void
     */
    public function testPMAGetHtmlForSqlQueryFormUpload()
    {
        //Call the test function
        $html = PMA_getHtmlForSqlQueryFormUpload();

        //validate 1: Browse your computer
        $this->assertContains(
            __('Browse your computer:'),
            $html
        );

        //validate 2: $GLOBALS['max_upload_size']
        $this->assertContains(
            PMA_Util::getFormattedMaximumUploadSize($GLOBALS['max_upload_size']),
            $html
        );
        $this->assertContains(
            PMA_Util::generateHiddenMaxFileSize($GLOBALS['max_upload_size']),
            $html
        );

        //validate 3: Dropdown Box
        $this->assertContains(
            PMA_generateCharsetDropdownBox(
                PMA_CSDROPDOWN_CHARSET,
                'charset_of_file', null, 'utf8', false
            ),
            $html
        );
    }

    /**
     * Test for PMA_getHtmlForSqlQueryFormBookmark
     *
     * @return void
     */
    public function testPMAGetHtmlForSqlQueryFormBookmark()
    {
        //Call the test function
        $html = PMA_getHtmlForSqlQueryFormBookmark();

        //validate 1: Bookmarked SQL query
        $this->assertContains(
            __('Bookmarked SQL query'),
            $html
        );
        $this->assertContains(
            '<select name="id_bookmark" id="id_bookmark">',
            $html
        );

        //validate 2: bookmark
        $key = "index1";
        $value = "table1";
        $option = '<option value="' . htmlspecialchars($key) . '">'
            . htmlspecialchars($value) . ' (shared)</option>' . "\n";
        $this->assertContains(
            $option,
            $html
        );
        $key = "index2";
        $value = "table2";
        $option = '<option value="' . htmlspecialchars($key) . '">'
            . htmlspecialchars($value) . ' (shared)</option>' . "\n";
        $this->assertContains(
            $option,
            $html
        );

        //validate 3: showDocu
        $this->assertContains(
            PMA_Util::showDocu('faq', 'faqbookmark'),
            $html
        );

        //validate 4: Footer
        $this->assertContains(
            '<fieldset id="bookmarkoptionsfooter" class="tblFooters">',
            $html
        );

        //validate 5: Go button
        $this->assertContains(
            __('Go'),
            $html
        );
    }

    /**
     * Test for PMA_getHtmlForSqlQueryFormInsert
     *
     * @return void
     */
    public function testPMAGetHtmlForSqlQueryFormInsert()
    {
        //Call the test function
        $query = "select * from PMA";
        $html = PMA_getHtmlForSqlQueryFormInsert($query);

        //validate 1: query
        $this->assertContains(
            htmlspecialchars($query),
            $html
        );

        //validate 2: enable auto select text in textarea
        $auto_sel = ' onclick="selectContent(this, sql_box_locked, true);"';
        $this->assertContains(
            $auto_sel,
            $html
        );

        //validate 3: showMySQLDocu
        $this->assertContains(
            PMA_Util::showMySQLDocu('SELECT'),
            $html
        );

        //validate 4: $fields_list
        $this->assertContains(
            '<input type="button" value="DELETE" id="delete"',
            $html
        );
        $this->assertContains(
            '<input type="button" value="UPDATE" id="update"',
            $html
        );
        $this->assertContains(
            '<input type="button" value="INSERT" id="insert"',
            $html
        );
        $this->assertContains(
            '<input type="button" value="SELECT" id="select"',
            $html
        );
        $this->assertContains(
            '<input type="button" value="SELECT *" id="selectall"',
            $html
        );

        //validate 5: Clear button
        $this->assertContains(
            '<input type="button" value="DELETE" id="delete"',
            $html
        );
        $this->assertContains(
            __('Clear'),
            $html
        );
    }
}

