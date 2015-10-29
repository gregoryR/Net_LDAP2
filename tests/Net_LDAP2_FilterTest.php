<?php
require_once 'Net/LDAP2/Filter.php';

/**
 * Test class for Net_LDAP2_Filter.
 * Generated by PHPUnit_Util_Skeleton on 2007-10-09 at 10:34:23.
 */
class Net_LDAP2_FilterTest extends PHPUnit_Framework_TestCase {
    /**
    * @var string   default filter string to test with
    */
    var $filter_str = '(&(cn=foo)(ou=bar))';

    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main() {
        require_once "PHPUnit/TextUI/TestRunner.php";

        $suite  = new PHPUnit_Framework_TestSuite("Net_LDAP2_FilterTest");
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp() {
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown() {
    }

    /**
     * This tests the perl compatible creation of filters through parsing of an filter string
     */
    public function testCreatePerlCompatible() {
        $filter_o = new Net_LDAP2_Filter($this->filter_str);
        $this->assertInstanceOf('Net_LDAP2_Filter', $filter_o);
        $this->assertEquals($this->filter_str, $filter_o->asString());

        $filter_o_err = new Net_LDAP2_Filter('some bad filter');
        $this->assertInstanceOf('PEAR_Error', $filter_o_err->asString());
    }

    /**
     * Test correct parsing of filter strings through parse()
     */
    public function testParse() {
       $parsed_dmg = Net_LDAP2_Filter::parse('some_damaged_filter_str');
       $this->assertInstanceOf('PEAR_Error', $parsed_dmg);

       $parsed_dmg2 = Net_LDAP2_Filter::parse('(invalid=filter)(because=~no-surrounding brackets)');
       $this->assertInstanceOf('PEAR_Error', $parsed_dmg2);

       $parsed_dmg3 = Net_LDAP2_Filter::parse('((invalid=filter)(because=log_op is missing))');
       $this->assertInstanceOf('PEAR_Error', $parsed_dmg3);

       $parsed_dmg4 = Net_LDAP2_Filter::parse('(invalid-because-becauseinvalidoperator)');
       $this->assertInstanceOf('PEAR_Error', $parsed_dmg4);

       $parsed_dmg5 = Net_LDAP2_Filter::parse('(&(filterpart>=ok)(part2=~ok)(filterpart3_notok---becauseinvalidoperator))');
       $this->assertInstanceOf('PEAR_Error', $parsed_dmg5);

       // To verify bug #19364 is fixed
       $parsed_dmg6 = Net_LDAP2_Filter::parse('(|((invalid-because-too-many-open-parens=x)(a=c))');
       $this->assertInstanceOf('PEAR_Error', $parsed_dmg6);
       $parsed_dmg7 = Net_LDAP2_Filter::parse('(|(invalid-because-too-many-close-parens=x)(a=c)))');
       $this->assertInstanceOf('PEAR_Error', $parsed_dmg7);

       $parsed1 = Net_LDAP2_Filter::parse($this->filter_str);
       $this->assertInstanceOf('Net_LDAP2_Filter', $parsed1);
       $this->assertEquals($this->filter_str, $parsed1->asString());

       // To verify bug #16738 is fixed.
       // In 2.0.6 there was a problem with the splitting of the filter parts if the next part was also an combined filter
       $parsed2_str = "(&(&(objectClass=posixgroup)(objectClass=foogroup))(uniquemember=uid=eeggs,ou=people,o=foo))";
       $parsed2 = Net_LDAP2_Filter::parse($parsed2_str);
       $this->assertInstanceOf('Net_LDAP2_Filter', $parsed2);
       $this->assertEquals($parsed2_str, $parsed2->asString());

        // To verify bug #17057 is fixed
        // In 2.0.7 there was a problem parsing certain not-combined filter strings.
       $parsed3_str = "(!(jpegPhoto=*))";
       $parsed3    = Net_LDAP2_Filter::parse($parsed3_str);
       $this->assertInstanceOf('Net_LDAP2_Filter', $parsed3);
       $this->assertEquals($parsed3_str, $parsed3->asString());

       $parsed3_complex_str = "(&(someAttr=someValue)(!(jpegPhoto=*)))";
       $parsed3_complex     = Net_LDAP2_Filter::parse($parsed3_complex_str);
       $this->assertInstanceOf('Net_LDAP2_Filter', $parsed3_complex);
       $this->assertEquals($parsed3_complex_str, $parsed3_complex->asString());

    }


    /**
     * This tests the basic create() method of creating filters
     */
    public function testCreate() {
        // Test values and an array containing the filter
        // creating methods and an regex to test the resulting filter
        $testattr = 'testattr';
        $testval  = 'testval';
        $combinations = array(
            'equals'         => "/\($testattr=$testval\)/",
            'equals'         => "/\($testattr=$testval\)/",
            'begins'         => "/\($testattr=$testval\*\)/",
            'ends'           => "/\($testattr=\*$testval\)/",
            'contains'       => "/\($testattr=\*$testval\*\)/",
            'greater'        => "/\($testattr>$testval\)/",
            'less'           => "/\($testattr<$testval\)/",
            'greaterorequal' => "/\($testattr>=$testval\)/",
            'lessorequal'    => "/\($testattr<=$testval\)/",
            'approx'         => "/\($testattr~=$testval\)/",
            'any'            => "/\($testattr=\*\)/"
        );
        // generate negating tests with supported operator combinations
        foreach ($combinations as $match => $regex) {
            $regex = preg_replace('#^/|/$#', '', $regex); // treat regex, so we can extend it easily
            $combinations['not '.$match] = "/\(!$regex\)/";
            $combinations['not_'.$match] = "/\(!$regex\)/";
            $combinations['not-'.$match] = "/\(!$regex\)/";
            $combinations['! '.$match]   = "/\(!$regex\)/";
            $combinations['!_'.$match]   = "/\(!$regex\)/";
            $combinations['!-'.$match]   = "/\(!$regex\)/";
        }

        // perform tests
        foreach ($combinations as $match => $regex) {
            // escaping is tested in util class
            $filter = Net_LDAP2_Filter::create($testattr, $match, $testval, false);

            $this->assertInstanceOf('Net_LDAP2_Filter', $filter);
            $this->assertRegExp($regex, $filter->asString(), "Filter generation failed for MatchType: $match");
        }

        // test creating failure
        $filter = Net_LDAP2_Filter::create($testattr, 'test_undefined_matchingrule', $testval);
        $this->assertInstanceOf('PEAR_Error', $filter);
    }

    /**
     * Tests, if asString() works
     */
    public function testAsString() {
        $filter = Net_LDAP2_Filter::create('foo', 'equals', 'bar');
        $this->assertInstanceOf('Net_LDAP2_Filter', $filter);
        $this->assertEquals('(foo=bar)', $filter->asString());
        $this->assertEquals('(foo=bar)', $filter->as_string());
    }

    /**
     * Tests, if printMe() works
     */
    public function testPrintMe() {
        if (substr(strtolower(PHP_OS), 0,3) == 'win') {
            $testfile = '/tmp/Net_LDAP2_Filter_printMe-Testfile';
        } else {
            $testfile = 'c:\Net_LDAP2_Filter_printMe-Testfile';
        }
        $filter = Net_LDAP2_Filter::create('testPrintMe', 'equals', 'ok');
        $this->assertInstanceOf('Net_LDAP2_Filter', $filter);

        // print success:
        ob_start();
        $printresult = $filter->printMe();
        ob_end_clean();
        $this->assertTrue($printresult);

        // PrintMe if Filehandle is an error (e.g. if some PEAR-File db is used):
        $err = new PEAR_Error();
        $this->assertInstanceOf('PEAR_Error', $filter->printMe($err));

        // PrintMe if filter is damaged,
        // $filter_dmg is used below too, to test printing to a file with
        // damaged filter
        $filter_dmg = new Net_LDAP2_Filter('damaged_filter_string');

        // write success:
        $file = @fopen($testfile, 'w');
        if (is_writable($testfile) && $file) {
            $this->assertTrue($filter->printMe($file));
            $this->assertInstanceOf('PEAR_Error', $filter_dmg->printMe($file)); // dmg. filter
            @fclose($file);
        } else {
            $this->markTestSkipped("$testfile could not be opened in write mode, skipping write test");
        }
        // write failure:
        $file = @fopen($testfile, 'r');
        if (is_writable($testfile) && $file) {
            $this->assertInstanceOf('PEAR_Error', $filter->printMe($file));
            @fclose($file);
            @unlink($testfile);
        } else {
            $this->markTestSkipped("$testfile could not be opened in read mode, skipping write test");
        }
    }

    /**
     * This tests the basic cobination of filters
     */
    public function testCombine() {
        // Setup
        $filter0 = Net_LDAP2_Filter::create('foo', 'equals', 'bar');
        $this->assertInstanceOf('Net_LDAP2_Filter', $filter0);

        $filter1 = Net_LDAP2_Filter::create('bar', 'equals', 'foo');
        $this->assertInstanceOf('Net_LDAP2_Filter', $filter1);

        $filter2 = Net_LDAP2_Filter::create('you', 'equals', 'me');
        $this->assertInstanceOf('Net_LDAP2_Filter', $filter2);

        $filter3 = new Net_LDAP2_Filter('(perlinterface=used)');
        $this->assertInstanceOf('Net_LDAP2_Filter', $filter3);

        // Negation test
        $filter_not1 = Net_LDAP2_Filter::combine('not', $filter0);
        $this->assertInstanceOf('Net_LDAP2_Filter', $filter_not1, 'Negation failed for literal NOT');
        $this->assertEquals('(!(foo=bar))', $filter_not1->asString());

        $filter_not2 = Net_LDAP2_Filter::combine('!', $filter0);
        $this->assertInstanceOf('Net_LDAP2_Filter', $filter_not2, 'Negation failed for logical NOT');
        $this->assertEquals('(!(foo=bar))', $filter_not2->asString());

        $filter_not3 = Net_LDAP2_Filter::combine('!', $filter0->asString());
        $this->assertInstanceOf('Net_LDAP2_Filter', $filter_not3, 'Negation failed for logical NOT');
        $this->assertEquals('(!'.$filter0->asString().')', $filter_not3->asString());


        // Combination test: OR
        $filter_comb_or1 = Net_LDAP2_Filter::combine('or', array($filter1, $filter2));
        $this->assertInstanceOf('Net_LDAP2_Filter', $filter_comb_or1, 'Combination failed for literal OR');
        $this->assertEquals('(|(bar=foo)(you=me))', $filter_comb_or1->asString());

        $filter_comb_or2 = Net_LDAP2_Filter::combine('|', array($filter1, $filter2));
        $this->assertInstanceOf('Net_LDAP2_Filter', $filter_comb_or2, 'combination failed for logical OR');
        $this->assertEquals('(|(bar=foo)(you=me))', $filter_comb_or2->asString());


        // Combination test: AND
        $filter_comb_and1 = Net_LDAP2_Filter::combine('and', array($filter1, $filter2));
        $this->assertInstanceOf('Net_LDAP2_Filter', $filter_comb_and1, 'Combination failed for literal AND');
        $this->assertEquals('(&(bar=foo)(you=me))', $filter_comb_and1->asString());

        $filter_comb_and2 = Net_LDAP2_Filter::combine('&', array($filter1, $filter2));
        $this->assertInstanceOf('Net_LDAP2_Filter', $filter_comb_and2, 'combination failed for logical AND');
        $this->assertEquals('(&(bar=foo)(you=me))', $filter_comb_and2->asString());


        // Combination test: using filter created with perl interface
        $filter_comb_perl1 = Net_LDAP2_Filter::combine('and', array($filter1, $filter3));
        $this->assertInstanceOf('Net_LDAP2_Filter', $filter_comb_perl1, 'Combination failed for literal AND');
        $this->assertEquals('(&(bar=foo)(perlinterface=used))', $filter_comb_perl1->asString());

        $filter_comb_perl2 = Net_LDAP2_Filter::combine('&', array($filter1, $filter3));
        $this->assertInstanceOf('Net_LDAP2_Filter', $filter_comb_perl2, 'combination failed for logical AND');
        $this->assertEquals('(&(bar=foo)(perlinterface=used))', $filter_comb_perl2->asString());


        // Combination test: using filter_str instead of object
        $filter_comb_fstr1 = Net_LDAP2_Filter::combine('and', array($filter1, '(filter_str=foo)'));
        $this->assertInstanceOf('Net_LDAP2_Filter', $filter_comb_fstr1, 'Combination failed for literal AND using filter_str');
        $this->assertEquals('(&(bar=foo)(filter_str=foo))', $filter_comb_fstr1->asString());


        // Combination test: deep combination
        $filter_comp_deep = Net_LDAP2_Filter::combine('and',array($filter2, $filter_not1, $filter_comb_or1, $filter_comb_perl1));
        $this->assertInstanceOf('Net_LDAP2_Filter', $filter_comp_deep, 'Deep combination failed!');
        $this->assertEquals('(&(you=me)(!(foo=bar))(|(bar=foo)(you=me))(&(bar=foo)(perlinterface=used)))', $filter_comp_deep->AsString());


        // Test failure in combination
        $damaged_filter  = Net_LDAP2_Filter::create('foo', 'test_undefined_matchingrule', 'bar');
        $this->assertInstanceOf('PEAR_Error', $damaged_filter);
        $filter_not_dmg0 = Net_LDAP2_Filter::combine('not', $damaged_filter);
        $this->assertInstanceOf('PEAR_Error', $filter_not_dmg0);

        $filter_not_dmg0s = Net_LDAP2_Filter::combine('not', 'damaged_filter_str');
        $this->assertInstanceOf('PEAR_Error', $filter_not_dmg0s);

        $filter_not_multi = Net_LDAP2_Filter::combine('not', array($filter0, $filter1));
        $this->assertInstanceOf('PEAR_Error', $filter_not_multi);

        $filter_not_dmg1 = Net_LDAP2_Filter::combine('not', null);
        $this->assertInstanceOf('PEAR_Error', $filter_not_dmg1);

        $filter_not_dmg2 = Net_LDAP2_Filter::combine('and', $filter_not1);
        $this->assertInstanceOf('PEAR_Error', $filter_not_dmg2);

        $filter_not_dmg3 = Net_LDAP2_Filter::combine('and', array($filter_not1));
        $this->assertInstanceOf('PEAR_Error', $filter_not_dmg3);

        $filter_not_dmg4 = Net_LDAP2_Filter::combine('and', $filter_not1);
        $this->assertInstanceOf('PEAR_Error', $filter_not_dmg4);

        $filter_not_dmg5 = Net_LDAP2_Filter::combine('or', array($filter_not1));
        $this->assertInstanceOf('PEAR_Error', $filter_not_dmg5);

        $filter_not_dmg5 = Net_LDAP2_Filter::combine('some_unknown_method', array($filter_not1));
        $this->assertInstanceOf('PEAR_Error', $filter_not_dmg5);

        $filter_not_dmg6 = Net_LDAP2_Filter::combine('and', array($filter_not1, 'some_invalid_filterstring'));
        $this->assertInstanceOf('PEAR_Error', $filter_not_dmg6);

        $filter_not_dmg7 = Net_LDAP2_Filter::combine('and', array($filter_not1, $damaged_filter));
        $this->assertInstanceOf('PEAR_Error', $filter_not_dmg7);

        $filter_not_dmg8 = Net_LDAP2_Filter::combine('and', array($filter_not1, null));
        $this->assertInstanceOf('PEAR_Error', $filter_not_dmg8);
    }

    /**
    * Test getComponents()
    */
    public function testGetComponents() {
        // make up some filters to test
        $filter = Net_LDAP2_Filter::create('foo', 'equals', 'bar');
        $this->assertEquals(array('foo', '=', 'bar'), $filter->getComponents());

        $filter = Net_LDAP2_Filter::create('foo', 'begins', 'bar');
        $this->assertEquals(array('foo', '=', 'bar*'), $filter->getComponents());

        $filter = Net_LDAP2_Filter::create('foo', 'ends', 'bar');
        $this->assertEquals(array('foo', '=', '*bar'), $filter->getComponents());

        $filter = Net_LDAP2_Filter::create('foo', 'contains', 'bar');
        $this->assertEquals(array('foo', '=', '*bar*'), $filter->getComponents());

        $filter = Net_LDAP2_Filter::create('foo', 'any');
        $this->assertEquals(array('foo', '=', '*'), $filter->getComponents());

        $filter = Net_LDAP2_Filter::create('foo', 'greater', '1234');
        $this->assertEquals(array('foo', '>', '1234'), $filter->getComponents());

        $filter = Net_LDAP2_Filter::create('foo', 'less', '1234');
        $this->assertEquals(array('foo', '<', '1234'), $filter->getComponents());

        $filter = Net_LDAP2_Filter::create('foo', 'greaterOrEqual', '1234');
        $this->assertEquals(array('foo', '>=', '1234'), $filter->getComponents());

        $filter = Net_LDAP2_Filter::create('foo', 'lessOrEqual', '1234');
        $this->assertEquals(array('foo', '<=', '1234'), $filter->getComponents());

        $filter = Net_LDAP2_Filter::create('foo', 'approx', '1234');
        $this->assertEquals(array('foo', '~=', '1234'), $filter->getComponents());


        // negative testing: non-leaf filter
        $filter = Net_LDAP2_Filter::combine('and', array(Net_LDAP2_Filter::create('foo', 'any'), Net_LDAP2_Filter::create('foo', 'equals', 'bar')));
        $this->assertInstanceOf('PEAR_Error', $filter->getComponents());

    }

    /**
    * Test match()
    */
    public function testMatch() {
        // make up some local test entry
        $entry1 = Net_LDAP2_Entry::createFresh('cn=Simpson Homer,l=springfield,c=usa',
            array(
                'cn'             => 'Simpson Homer',
                'sn'             => 'Simpson',
                'givenName'      => 'Homer',
                'fingers'        => 5,
                'hairColor'      => 'black',
                'donutsConsumed' => 4521875663232,
                'height'         => '175',
                'mail'           => 'homer@iLikeBlueToweredHair.com',
                'objectClass'    => array('top', 'person', 'inetOrgPerson', 'myFancyTestClass'),
                )
        );
        $entry2 = Net_LDAP2_Entry::createFresh('cn=Simpson Bart,l=springfield,c=usa',
            array(
                'cn'             => 'Simpson Bart',
                'sn'             => 'Simpson',
                'givenName'      => 'Bart',
                'fingers'        => 5,
                'hairColor'      => 'yellow',
                'height'         => '120',
                'mail'           => 'bart@iHateSchool.com',
                'objectClass'    => array('top', 'person', 'inetOrgPerson', 'myFancyTestClass'),
                )   
        );
        $entry3 = Net_LDAP2_Entry::createFresh('cn=Brockman Kent,l=springfield,c=usa',
            array(      
                'cn'             => 'Brockman Kent',
                'sn'             => 'Brockman',
                'givenName'      => 'Kent',
                'fingers'        => 5,
                'hairColor'      => 'white',
                'height'         => '185',
                'mail'           => 'kent.brockman@channel6.com',
                'objectClass'    => array('top', 'person', 'inetOrgPerson', 'myFancyTestClass'),
                )   
        );

        $allEntries = array($entry1, $entry2, $entry3);

        // Simple matching on single entry
        $filter = Net_LDAP2_Filter::create('cn', 'equals', 'Simpson Homer');
        $this->assertEquals(1, $filter->matches($entry1));
	$filter = Net_LDAP2_Filter::create('cn', 'equals', 'son');
        $this->assertEquals(0, $filter->matches($entry1));

        $filter = Net_LDAP2_Filter::create('mail', 'begins', 'Hom');
        $this->assertEquals(1, $filter->matches($entry1));

        $filter = Net_LDAP2_Filter::create('objectClass', 'contains', 'org');  // note the lowercase of 'org', as DirSTR is usually syntax CaseIgnore
        $this->assertEquals(1, $filter->matches($entry1));

        // Simple negative tests on single entry
        $filter = Net_LDAP2_Filter::create('givenName', 'equals', 'Lisa-is-nonexistent');
        $this->assertEquals(0, $filter->matches($entry1));

        // Simple tests with multiple entries
        $filter = Net_LDAP2_Filter::create('cn', 'begins', 'Nomatch');
        $this->assertEquals(0, $filter->matches($allEntries));

        $filter = Net_LDAP2_Filter::create('cn', 'begins', 'Simpson Ho');
        $this->assertEquals(1, $filter->matches($allEntries));

        $filter = Net_LDAP2_Filter::create('cn', 'begins', 'Simpson');
        $this->assertEquals(2, $filter->matches($allEntries));

        // test with retrieving the resulting entries
        $filter = Net_LDAP2_Filter::create('cn', 'begins', 'Simpson Ho');
        $filterresult = array();
        $this->assertEquals(1, $filter->matches($allEntries, $filterresult));
        $this->assertEquals(count($filterresult), $filter->matches($allEntries, $filterresult), "returned result and result counter differ!");
        $this->assertEquals($entry1->dn(), array_shift($filterresult)->dn(), "Filtered entry does not equal expected entry! filter='".$filter->asString()."'");

        // make sure return values are consistent with input and that all entries are found
        $filter = Net_LDAP2_Filter::parse('(objectClass=*)');
        $filterresult = array();
        $this->assertEquals(count($allEntries), $filter->matches($allEntries, $filterresult), "returned result does not match input data count");
        $this->assertEquals(count($filterresult), $filter->matches($allEntries, $filterresult), "returned result and result counter differ!");

	// Test for compliant "any" filtering:
	// Only entries should be returned, that have the attribute
	// Negation: Only Entries that don't have the attribute set at all
	$filter = Net_LDAP2_Filter::create('donutsConsumed', 'any'); // only homer consume donuts
	$filterresult = array();
	$this->assertEquals(1, $filter->matches($allEntries, $filterresult));
	$this->assertEquals($entry1->dn(), array_shift($filterresult)->dn(), "Filtered entry does not equal expected entry! filter='".$filter->asString()."'");
	
	$filter = Net_LDAP2_Filter::combine('not', $filter); // all but homer consume donuts
        $this->assertEquals(count($allEntries)-1, $filter->matches($allEntries, $filterresult), "Filtered entry does not equal expected entry! filter='".$filter->asString()."'");

        // NOT combination test
        $filter = Net_LDAP2_Filter::create('givenName', 'not equals', 'Homer');
        $filterresult = array();
        $this->assertEquals(2, $filter->matches($allEntries, $filterresult));
        $this->assertEquals($entry2->dn(), array_shift($filterresult)->dn(), "Filtered entry does not equal expected entry! filter='".$filter->asString()."'");
        $this->assertEquals($entry3->dn(), array_shift($filterresult)->dn(), "Filtered entry does not equal expected entry! filter='".$filter->asString()."'");
        
        // OR combination test
        $filter1 = Net_LDAP2_Filter::create('sn', 'equals', 'Simpson');
        $filter2 = Net_LDAP2_Filter::create('givenName', 'equals', 'Kent');
        $filter_or = Net_LDAP2_Filter::combine('or', array($filter1, $filter2));
        $filterresult = array();
        $this->assertEquals(3, $filter_or->matches($allEntries, $filterresult));

        // AND combination test
        $filter1 = Net_LDAP2_Filter::create('sn', 'equals', 'Simpson');
        $filter2 = Net_LDAP2_Filter::create('givenName', 'equals', 'Bart');
        $filter_and = Net_LDAP2_Filter::combine('and', array($filter1, $filter2));
        $filterresult = array();
        $filter_and->matches($allEntries, $filterresult);
        $this->assertEquals(1, $filter_and->matches($allEntries, $filterresult), "AND Filter failed '".$filter_and->asString()."'");

        // AND, NOT and OR combined test
        $filter1 = Net_LDAP2_Filter::combine('or', array(
                Net_LDAP2_Filter::create('hairColor', 'equals', 'white'), // kent or...
                Net_LDAP2_Filter::create('hairColor', 'equals', 'black')  // ...homer
            ));
        $filter2 = Net_LDAP2_Filter::create('givenName', 'not equals', 'Homer'); // all except homer
        $filter_final = Net_LDAP2_Filter::combine('and', array($filter1, $filter2));
        $this->assertEquals(2, $filter1->matches($allEntries)); // kent and homer
        $this->assertEquals(2, $filter2->matches($allEntries)); // kent and bart
        $filterresult = array();
        $this->assertEquals(1, $filter_final->matches($allEntries, $filterresult)); // should leave only kent
        $this->assertEquals($entry3->dn(), array_shift($filterresult)->dn(), "Filtered entry does not equal expected entry! filter='".$filter_final->asString()."'");
        
        // [TODO]: Further tests for >, <, >=, <= and ~=, when they are implemented.
        // ...until then: negative testing for those cases
        foreach (array('>', '<', '>=', '<=', '~=') as $to) {
            $filter = Net_LDAP2_Filter::parse("(fingers${to}5)");
            $this->assertInstanceOf('PEAR_Error', $filter->matches($allEntries), "Valid operator succeeded: WRITE THE TESTCASE FOR IT!");
        }
    }

}
?>
