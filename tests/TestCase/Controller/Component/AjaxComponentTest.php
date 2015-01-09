<?php

namespace Ajax\Test\TestCase\Controller\Component;

use App\Model\AppModel;
use Cake\Controller\Component;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Ajax\Controller\Component\AjaxComponent;
use Cake\Network\Request;
use Cake\Network\Response;

/**
 */
class AjaxComponentTest extends TestCase {

	public $fixtures = array(
		'core.Sessions'
	);

	public function setUp() {
		parent::setUp();

		Configure::write('App.namespace', 'TestApp');

		Configure::delete('Ajax');

		$this->Controller = new AjaxComponentTestController(new Request, new Response);
		$this->Controller->initialize();
	}

	/**
	 * AjaxComponentTest::testNonAjax()
	 *
	 * @return void
	 */
	public function testNonAjax() {
		$this->Controller->startupProcess();
		$this->assertFalse($this->Controller->Components->Ajax->respondAsAjax);
	}

	/**
	 * AjaxComponentTest::testDefaults()
	 *
	 * @return void
	 */
	public function testDefaults() {
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

		$this->Controller->startupProcess();
		$this->assertTrue($this->Controller->Components->Ajax->respondAsAjax);

		$this->Controller->request->session()->setFlash('A message', 'custom');
		$session = $this->Controller->request->session()->read('Message.flash');
		$expected = array(
			'message' => 'A message',
			'element' => 'custom',
			'params' => array()
		);
		$this->assertEquals($expected, $session);

		$this->Controller->Components->Ajax->beforeRender($this->Controller);

		$this->assertEquals('Ajax.Ajax', $this->Controller->viewClass);
		$this->assertEquals($expected, $this->Controller->viewVars['_message']);

		$session = $this->Controller->request->session()->read('Message.flash');
		$this->assertNull($session);

		$this->Controller->redirect('/');
		$this->assertSame(array(), $this->Controller->response->header());

		$expected = array(
			'url' => Router::url('/', true),
			'status' => null,
			'exit' => true
		);
		$this->assertEquals($expected, $this->Controller->viewVars['_redirect']);
	}

	/**
	 * AjaxComponentTest::testAutoDetectOnFalse()
	 *
	 * @return void
	 */
	public function testAutoDetectOnFalse() {
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

		$this->Controller->Components->unload('Ajax');
		$this->Controller->Components->load('Ajax.Ajax', array('autoDetect' => false));

		$this->Controller->startupProcess();
		$this->assertFalse($this->Controller->Components->Ajax->respondAsAjax);
	}

	/**
	 * AjaxComponentTest::testAutoDetectOnFalseViaConfig()
	 *
	 * @return void
	 */
	public function testAutoDetectOnFalseViaConfig() {
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
		Configure::write('Ajax.autoDetect', false);

		$this->Controller->Components->unload('Ajax');
		$this->Controller->Components->load('Ajax.Ajax');

		$this->Controller->startupProcess();
		$this->assertFalse($this->Controller->Components->Ajax->respondAsAjax);
	}

	/**
	 * AjaxComponentTest::testToolsMultiMessages()
	 *
	 * @return void
	 */
	public function testToolsMultiMessages() {
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
		Configure::write('Ajax.flashKey', 'messages');

		$this->Controller->Components->unload('Ajax');
		$this->Controller->Components->load('Ajax.Ajax');

		$this->Controller->startupProcess();
		$this->assertTrue($this->Controller->Components->Ajax->respondAsAjax);

		$this->Controller->Flash->message('A message', 'success');
		$session = $this->Controller->request->session()->read('messages');
		$expected = array(
			'success' => array('A message')
		);
		$this->assertEquals($expected, $session);

		$this->Controller->Components->Ajax->beforeRender($this->Controller);
		$this->assertEquals('Ajax.Ajax', $this->Controller->viewClass);

		$this->assertEquals($expected, $this->Controller->viewVars['_message']);

		$session = $this->Controller->request->session()->read('messages');
		$this->assertNull($session);
	}

	/**
	 * AjaxComponentTest::testSetVars()
	 *
	 * @return void
	 */
	public function testSetVars() {
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

		$this->Controller->Components->unload('Ajax');

		$content = array('id' => 1, 'title' => 'title');
		$this->Controller->set(compact('content'));
		$this->Controller->set('_serialize', array('content'));

		$this->Controller->Components->load('Ajax.Ajax');
		$this->assertNotEmpty($this->Controller->viewVars);
		$this->assertNotEmpty($this->Controller->viewVars['_serialize']);
		$this->assertEquals('content', $this->Controller->viewVars['_serialize'][0]);
	}

	/**
	 * AjaxComponentTest::testSetVarsWithRedirect()
	 *
	 * @return void
	 */
	public function testSetVarsWithRedirect() {
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
		$this->Controller->startupProcess();

		$content = array('id' => 1, 'title' => 'title');
		$this->Controller->set(compact('content'));
		$this->Controller->set('_serialize', array('content'));

		$this->Controller->redirect('/');
		$this->assertSame(array(), $this->Controller->response->header());

		$expected = array(
			'url' => Router::url('/', true),
			'status' => null,
			'exit' => true
		);
		$this->assertEquals($expected, $this->Controller->viewVars['_redirect']);

		$this->Controller->set(array('_message' => 'test'));
		$this->Controller->redirect('/');
		$this->assertArrayHasKey('_message', $this->Controller->viewVars);

		$this->assertNotEmpty($this->Controller->viewVars);
		$this->assertNotEmpty($this->Controller->viewVars['_serialize']);
		$this->assertTrue(in_array('content', $this->Controller->viewVars['_serialize']));
	}
}

// Use Controller instead of AppController to avoid conflicts
class AjaxComponentTestController extends Controller {

	public $components = array('Session', 'Ajax.Ajax', 'Tools.Flash');

}