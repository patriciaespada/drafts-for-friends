<?php
/**
 * Class Get_Time_To_Expire_Test
 *
 * @package Drafts_For_Friends
 */

/**
 * Tests for the operation to get the formatted time for a particular draft to expire.
 */
class Get_Time_To_Expire_Test extends WP_UnitTestCase {

	/**
	 * Current timestamp
	 *
	 * @var int
	 */
	private $now;

	/**
	 * Instance of the plugin class
	 *
	 * @var Drafts_For_Friends
	 */
	private $drafts_for_friends;

	/**
	 * Initialize all needed variables before testing.
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->now                = current_time( 'timestamp' );
		$this->drafts_for_friends = new Drafts_For_Friends();
	}

	/**
	 * Reset variables after testing.
	 *
	 * @return void
	 */
	public function tearDown() {
		$this->now                = null;
		$this->drafts_for_friends = null;
	}

	/**
	 * Test is for current date the output is 1 minute (this is the less amount of time before Expire).
	 *
	 * @return void
	 */
	public function test_current_date() {
		$share = array(
			'expires' => $this->now,
		);

		$time_to_expire = $this->drafts_for_friends->get_time_to_expire( $share );

		$this->assertEquals( '1 minute', $time_to_expire );
	}

	/**
	 * Test if we remove some second from the current date the output will be Expired.
	 *
	 * @return void
	 */
	public function test_current_date_minus_second() {

		$share = array(
			'expires' => strtotime( '-1 second', $this->now ),
		);

		$time_to_expire = $this->drafts_for_friends->get_time_to_expire( $share );

		$this->assertEquals( 'Expired', $time_to_expire );
	}

	/**
	 * Test if we add some seconds from the current date the output will be the amount we added.
	 *
	 * @return void
	 */
	public function test_current_date_plus_seconds() {

		$share = array(
			'expires' => strtotime( '+120 second', $this->now ),
		);

		$time_to_expire = $this->drafts_for_friends->get_time_to_expire( $share );

		$this->assertEquals( '2 minutes', $time_to_expire );
	}

	/**
	 * Test if we add some minutes from the current date the output will be the amount we added.
	 *
	 * @return void
	 */
	public function test_current_date_plus_minutes() {

		$share = array(
			'expires' => strtotime( '+45 minute', $this->now ),
		);

		$time_to_expire = $this->drafts_for_friends->get_time_to_expire( $share );

		$this->assertEquals( '45 minutes', $time_to_expire );
	}

	/**
	 * Test if we add one hour from the current date the output will be the amount we added.
	 *
	 * @return void
	 */
	public function test_current_date_plus_one_hour() {

		$share = array(
			'expires' => strtotime( '+1 hour', $this->now ),
		);

		$time_to_expire = $this->drafts_for_friends->get_time_to_expire( $share );

		$this->assertEquals( '1 hour, 0 minutes', $time_to_expire );
	}

	/**
	 * Test if we add some hours from the current date the output will be the amount we added.
	 *
	 * @return void
	 */
	public function test_current_date_plus_hours() {

		$share = array(
			'expires' => strtotime( '+245 minute', $this->now ),
		);

		$time_to_expire = $this->drafts_for_friends->get_time_to_expire( $share );

		$this->assertEquals( '4 hours, 5 minutes', $time_to_expire );
	}

	/**
	 * Test if we add some days from the current date the output will be the amount we added.
	 *
	 * @return void
	 */
	public function test_current_date_plus_days() {

		$share = array(
			'expires' => strtotime( '+5 day +3 hour +12 minute', $this->now ),
		);

		$time_to_expire = $this->drafts_for_friends->get_time_to_expire( $share );

		$this->assertEquals( '5 days, 3 hours, 12 minutes', $time_to_expire );
	}

}
