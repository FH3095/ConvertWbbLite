<?php

namespace FH3095\ConvertWbbLite\event;

class main_listener implements 
		\Symfony\Component\EventDispatcher\EventSubscriberInterface
{

	static public function getSubscribedEvents()
	{
		return array(
			'core.notification_manager_add_notifications' => 'prevent_notifications',
			'core.markread_before' => 'prevent_markread'
		);
	}

	public function prevent_notifications($event)
	{
		$event['notify_users'] = array();
	}

	public function prevent_markread($event)
	{
		$event['should_markread'] = false;
	}
}
