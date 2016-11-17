package com.clout.cron.batch.jobitems;

import java.util.ArrayList;
import java.util.List;

import com.clout.cron.batch.model.Store;

public class StoresItem extends BaseItem {

	/**
	 * 
	 */
	private static final long serialVersionUID = 1L;
	
	public List<Store> stores = new ArrayList<Store>();

	public List<Store> getStores() {
		return stores;
	}

	public void setStores(List<Store> stores) {
		this.stores = stores;
	}

}
