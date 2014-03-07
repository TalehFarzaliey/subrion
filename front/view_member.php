<?php
//##copyright##

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	// display 404 if members are disabled
	if (!$iaCore->get('members_enabled'))
	{
		return iaView::errorPage(iaView::ERROR_NOT_FOUND);
	}

	$iaUsers = $iaCore->factory('users');

	if (isset($_GET['account_by']))
	{
		$_SESSION['account_by'] = $_GET['account_by'];
	}
	if (!isset($_SESSION['account_by']))
	{
		$_SESSION['account_by'] = 'username';
	}

	$filterBy = ($_SESSION['account_by'] == 'fullname') ? 'fullname' : 'username';
	$member = $iaUsers->getInfo($iaCore->requestPath[0], 'username');
	if (empty($member))
	{
		$member = $iaUsers->getInfo((int)$iaCore->requestPath[0]);
	}
	if (empty($member))
	{
		return iaView::errorPage(iaView::ERROR_NOT_FOUND);
	}

	$iaCore->factory('util');
	$iaPage = $iaCore->factory('page', iaCore::FRONT);

	$member['item'] = $iaUsers->getItemName();

	$iaCore->startHook('phpViewListingBeforeStart', array(
		'listing' => $member['id'],
		'item' => $member['item'],
		'title' => $member['fullname'],
		'url' => $iaView->iaSmarty->ia_url(array(
			'data' => $member,
			'item' => $member['item'],
			'type' => 'url'
		)),
		'desc' => $member['fullname']
	));

	$iaItem = $iaCore->factory('item');
	$iaCore->set('num_items_perpage', 20);

	$page = !empty($_GET['page']) ? (int)$_GET['page'] : 1;
	$page = ($page < 1) ? 1 : $page;
	$start = ($page - 1) * $iaCore->get('num_items_perpage');

	if (iaUsers::hasIdentity() && iaUsers::getIdentity()->id == $member['id'])
	{
		$iaItem->setItemTools(array(
			'title' => iaLanguage::get('edit'),
			'url' => $iaPage->getUrlByName('profile')
		));
	}

	$member = array_shift($iaItem->updateItemsFavorites(array($member), $member['item']));
	$member['items'] = array();

	// get fieldgroups
	$iaField = $iaCore->factory('field');
	list($sections, ) = $iaField->generateTabs($iaField->filterByGroup($member, $member['item']));

	// get all items added by this account
	$itemsList = $iaItem->getPackageItems();
	$itemsFlat = array();

	if ($array = $iaItem->getItemsInfo(true))
	{
		foreach ($array as $itemData)
		{
			if ($itemData['item'] != $member['item'] && ($iaItem->isExtrasExist($itemsList[$itemData['item']])))
			{
				$itemsFlat[] = $itemData['item'];
			}
		}
	}

	if (count($itemsFlat) > 0)
	{
		$limit = $iaCore->get('num_items_perpage');
		foreach ($itemsFlat as $itemName)
		{
			if ($class = $iaCore->factoryPackage('item', $itemsList[$itemName], iaCore::FRONT, $itemName))
			{
				if (method_exists($class, iaUsers::METHOD_NAME_GET_LISTINGS))
				{
					$result = $class->{iaUsers::METHOD_NAME_GET_LISTINGS}($member['id'], $start, $limit);
				}
				// TODO: this section will be removed from the 3.1.5 core
				// packages should implement the method above instead
				elseif (method_exists($class, 'addAccountTab'))
				{
					$result = $class->addAccountTab(null, $start, $limit, $member['id']);
				}
				//
				else
				{
					$result = null;
				}

				if (!is_null($result))
				{
					if ($result['items'])
					{
						// add tab in case items exist
						$sections[$itemName] = array();

						$result['items'] = $iaItem->updateItemsFavorites($result['items'], $itemName);
					}

					$member['items'][$itemName] = $result;
					$member['items'][$itemName]['fields'] = $iaField->filter($member['items'][$itemName]['items'], $itemName);
				}
			}
		}
	}

	$alpha = substr($member[$filterBy], 0, 1);
	$alpha || $alpha = substr($member['username'], 0, 1);
	$alpha = strtoupper($alpha);

	$iaView->set('subpage', $alpha);

	iaBreadcrumb::preEnd($alpha, $iaPage->getUrlByName('members') . $alpha . IA_URL_DELIMITER);

	$iaView->assign('item', $member);
	$iaView->assign('sections', $sections);

	$iaView->title($iaView->title() . ' - ' . (empty($member['fullname']) ? $member['username'] : $member['fullname']));

	$iaView->display('view-member');
}