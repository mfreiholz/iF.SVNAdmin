<?php

abstract class SearchableGroupProvider extends GroupProvider {

	public function search($query, $offset = 0, $limit = -1) {
		$list = new ItemList();
		$foundItems = array();
		foreach ($this->getGroups()->getItems() as &$item) {
			if (stripos($item->getId(), $query) !== false) {
				$foundItems[] = $item;
			}
			else if (stripos($item->getName(), $query) !== false) {
				$foundItems[] = $item;
			}
			else if (stripos($item->getDisplayName(), $query) !== false) {
				$foundItems[] = $item;
			}
		}
		$list->initialize($foundItems, false);
		return $list;
	}

}