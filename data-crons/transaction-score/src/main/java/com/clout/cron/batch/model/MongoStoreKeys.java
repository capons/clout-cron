package com.clout.cron.batch.model;

import org.springframework.data.mongodb.core.mapping.Field;

public class MongoStoreKeys {
	
	private String id;
	private String name;
	
	@Field("store_id")
	private String storeId;

	public String getId() {
		return id;
	}

	public void setId(String id) {
		this.id = id;
	}

	public String getName() {
		return name;
	}

	public void setName(String name) {
		this.name = name;
	}

	public String getStoreId() {
		return storeId;
	}

	public void setStoreId(String storeId) {
		this.storeId = storeId;
	}

}
