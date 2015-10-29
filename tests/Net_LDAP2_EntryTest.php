<?php
require_once 'Net_LDAP2Test.php'; // for config methods

require_once 'Net/LDAP2/Entry.php';
require_once 'Net/LDAP2/Entry.php';

/**
 * Test class for Net_LDAP2_Entry.
 * Generated by PHPUnit_Util_Skeleton on 2007-10-09 at 10:33:12.
 */
class Net_LDAP2_EntryTest extends PHPUnit_Framework_TestCase {
   /**
    * Stores the LDAP configuration
    */
    var $ldapcfg = false;

    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main() {
        require_once "PHPUnit/TextUI/TestRunner.php";

        $suite  = new PHPUnit_Framework_TestSuite("Net_LDAP2_EntryTest");
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp() {
        $this->ldapcfg = $this->getTestConfig();
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
     * This checks if a valid LDAP testconfig is present and loads it.
     *
     * If so, it is loaded and returned as array. If not, false is returned.
     *
     * @return false|array
     */
    public function getTestConfig() {
        $config = false;
        $file = dirname(__FILE__).'/ldapconfig.ini';
        if (file_exists($file) && is_readable($file)) {
            $config = parse_ini_file($file, true);
        } else {
            return false;
        }
        // validate ini
        $v_error = $file.' is probably invalid. Did you quoted values correctly?';
        $this->assertTrue(array_key_exists('global', $config), $v_error);
        $this->assertTrue(array_key_exists('test', $config), $v_error);
        $this->assertEquals(7, count($config['global']), $v_error);
        $this->assertEquals(7, count($config['test']), $v_error);

        // reformat things a bit, for convinience
        $config['global']['server_binddn'] =
            $config['global']['server_binddn'].','.$config['global']['server_base_dn'];
        $config['test']['existing_attrmv'] = explode('|', $config['test']['existing_attrmv']);
        return $config;
    }

    /**
    * Establishes a working connection
    *
    * @return Net_LDAP2
    */
    public function &connect() {
        // Check extension
        if (true !== Net_LDAP2::checkLDAPExtension()) {
            $this->markTestSkipped('PHP LDAP extension not found or not loadable. Skipped Test.');
        }

        // Simple working connect and privilegued bind
        $lcfg = array(
                'host'   => $this->ldapcfg['global']['server_address'],
                'port'   => $this->ldapcfg['global']['server_port'],
                'basedn' => $this->ldapcfg['global']['server_base_dn'],
                'binddn' => $this->ldapcfg['global']['server_binddn'],
                'bindpw' => $this->ldapcfg['global']['server_bindpw'],
                'filter' => '(ou=*)',
            );
        $ldap = Net_LDAP2::connect($lcfg);
        $this->assertInstanceOf('Net_LDAP2', $ldap, 'Connect failed but was supposed to work. Check credentials and host address. If those are correct, file a bug!');
        return $ldap;
    }

/* ---------- TESTS ---------- */

    /**
     * @todo Implement testCreateFresh().
     */
    public function testCreateFresh() {
        // test failing creation
        $t = Net_LDAP2_Entry::createFresh("cn=test", "I should be an array");
        $this->assertTrue(Net_LDAP2::isError($t), 'Creating fresh entry succeeded but was supposed to fail!');

        // test failing creation
        $t = Net_LDAP2_Entry::createFresh("cn=test", 
            array(
                'attr1' => 'single',
                'attr2' => array('mv1', 'mv2')
                )
        );
        $this->assertInstanceOf('Net_LDAP2_Entry', $t, 'Creating fresh entry failed but was supposed to succeed!');
    }

    /**
     * @todo Implement testCreateExisting().
     */
    public function testCreateExisting() {
        // Remove the following line when you implement this test.
        $this->markTestIncomplete(
          "This test has not been implemented yet."
        );
    }

    /**
    * Test currentDN and API of move
    */
    public function testCurrentDN() {
        $entry = Net_LDAP2_Entry::createFresh('cn=footest,ou=example,dc=com', array('cn' => 'foo'));

	// test initial state
	$this->assertEquals($entry->dn(), $entry->currentDN()); // equal DNs
        $this->assertFalse($entry->willBeMoved());

	// prepare move
	$entry->dn('cn=newDN,ou=example,dc=com');

	// test again
	$this->assertNotEquals($entry->dn(), $entry->currentDN()); // equal DNs
        $this->assertTrue($entry->willBeMoved());
    }

    /**
     * @todo Implement testDn().
     */
    public function testDn() {
        // Remove the following line when you implement this test.
        $this->markTestIncomplete(
          "This test has not been implemented yet."
        );
    }

    /**
     * @todo Implement test_setAttributes().
     */
    public function test_setAttributes() {
        // Remove the following line when you implement this test.
        $this->markTestIncomplete(
          "This test has not been implemented yet."
        );
    }

    /**
     * @todo Implement testGetValues().
     */
    public function testGetValues() {
        // Remove the following line when you implement this test.
        $this->markTestIncomplete(
          "This test has not been implemented yet."
        );
    }

    /**
     * @todo Implement testGetValue().
     */
    public function testGetValue() {
        // make up some local entry
        $entry = Net_LDAP2_Entry::createFresh("cn=test",
            array(
                'attr1' => 'single',
                'attr2' => array('mv1', 'mv2')
                )
        );
    
        // test default behavior
        $this->assertEquals('single', $entry->getValue('attr1'));
        $this->assertEquals(array('mv1', 'mv2'), $entry->getValue('attr2'));
        $this->assertEquals(false, $entry->getValue('nonexistent'));

        // test option "single"
        $this->assertEquals('single', $entry->getValue('attr1', 'single'));
        $this->assertEquals('mv1', $entry->getValue('attr2', 'single'));
        $this->assertEquals(false, $entry->getValue('nonexistent', 'single'));

        // test option "all"
        $this->assertEquals(array('single'), $entry->getValue('attr1', 'all'));
        $this->assertEquals(array('mv1', 'mv2'), $entry->getValue('attr2', 'all'));
        $this->assertEquals(array(), $entry->getValue('nonexistent', 'all'));
    }

    /**
     * @todo Implement testGet_value().
     */
    public function testGet_value() {
        // Remove the following line when you implement this test.
        $this->markTestIncomplete(
          "This test has not been implemented yet."
        );
    }

    /**
     * @todo Implement testAttributes().
     */
    public function testAttributes() {
        // Remove the following line when you implement this test.
        $this->markTestIncomplete(
          "This test has not been implemented yet."
        );
    }

    /**
     * @todo Implement testExists().
     */
    public function testExists() {
        // Remove the following line when you implement this test.
        $this->markTestIncomplete(
          "This test has not been implemented yet."
        );
    }

    /**
     * @todo Implement testAdd().
     */
    public function testAdd() {
        // Remove the following line when you implement this test.
        $this->markTestIncomplete(
          "This test has not been implemented yet."
        );
    }

    /**
     * @todo Implement testDelete().
     */
    public function testDelete() {
        // Remove the following line when you implement this test.
        $this->markTestIncomplete(
          "This test has not been implemented yet."
        );
    }

    /**
     * @todo Implement testReplace().
     */
    public function testReplace() {
        // Remove the following line when you implement this test.
        $this->markTestIncomplete(
          "This test has not been implemented yet."
        );
    }

    /**
     * @todo Implement testUpdate().
     */
    public function testUpdate() {
        // Remove the following line when you implement this test.
        $this->markTestIncomplete(
          "This test has not been implemented yet."
        );
    }

    /**
     * @todo Implement test_getAttrName().
     */
    public function test_getAttrName() {
        // Remove the following line when you implement this test.
        $this->markTestIncomplete(
          "This test has not been implemented yet."
        );
    }

    /**
     * @todo Implement testGetLDAP().
     */
    public function testGetLDAP() {
        // Remove the following line when you implement this test.
        $this->markTestIncomplete(
          "This test has not been implemented yet."
        );
    }

    /**
     * @todo Implement testSetLDAP().
     */
    public function testSetLDAP() {
        // Remove the following line when you implement this test.
        $this->markTestIncomplete(
          "This test has not been implemented yet."
        );
    }

    /**
     * @todo Implement testPreg_match().
     */
    public function testPreg_match() {
        // Remove the following line when you implement this test.
        $this->markTestIncomplete(
          "This test has not been implemented yet."
        );
    }
}
?>
