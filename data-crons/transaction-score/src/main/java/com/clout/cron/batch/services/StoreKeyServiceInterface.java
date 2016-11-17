package com.clout.cron.batch.services;

import java.util.Map;

public interface StoreKeyServiceInterface {

	public void write(Map<String, String> storeIds) throws Exception;
	
}
