<?php
require_once("../websocket.client.php");
require_once("../websocket.admin.php");
require_once('simpletest/autorun.php');

/**
 * These tests need the 'demo.php' server to be running
 *
 * @author Chris
 *
 */
class test extends UnitTestCase {
	function test_echoResourceHandlerResponse(){
		$input = "Hello World!";
		$msg = WebSocketMessage::create($input);

		$client = new WebSocket("wss://127.0.0.1:12345/echo/");
		$client->open();
		$client->sendMessage($msg);

		$msg = $client->readMessage();

		$client->close();
		$this->assertEqual($input, $msg->getData());
	}

	function test_DoubleEchoResourceHandlerResponse(){
		$input = "Hello World!";
		$msg = WebSocketMessage::create($input);

		$client = new WebSocket("wss://127.0.0.1:12345/echo/");
		$client->setTimeOut(1000);
		$client->open();
		$client->sendMessage($msg);
		$client->sendMessage($msg);

		$msg = $client->readMessage();
		$msg2= $client->readMessage();

		$client->close();
		$this->assertEqual($input, $msg->getData());

		$this->assertEqual($input, $msg2->getData());
	}

	function test_AdminPing(){
		$msg = WebSocketAdminMessage::create("shutdown");

		$client = new WebSocketAdminClient("wss://127.0.0.1:12345/echo","superdupersecretkey");
		$client->open();
		$client->sendMessage($msg);

		$msg = $client->readFrame();
		$client->close();

		$this->assertEqual(WebSocketOpcode::PongFrame, $msg->getType());
	}

	function test_pingResponse(){

		$frame = WebSocketFrame::create(WebSocketOpcode::PingFrame);

		$client = new WebSocket("wss://127.0.0.1:12345/");
		$client->open();
		$client->sendFrame($frame);

		$frame = $client->readFrame();

		$client->close();
		$this->assertEqual(WebSocketOpcode::PongFrame, $frame->getType());
	}

	function test_closeResponse(){
		$frame = WebSocketFrame::create(WebSocketOpcode::CloseFrame);

		$client = new WebSocket("wss://127.0.0.1:12345/");
		$client->open();
		$client->sendFrame($frame);

		$frame = $client->readFrame();

		$client->close();

		// When sending a Close frame, the client needs to receive a Close frame as well before the connection is disconnected
		$this->assertEqual(WebSocketOpcode::CloseFrame, $frame->getType());

		// Specs dictate that the frame should be masked!
		$this->assertEqual($frame->isMasked(), true);
	}
}