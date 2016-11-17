package com.clout.cron.batch.storekeywords;

import java.io.Serializable;

/**
 * 
 * @author Ung
 *
 */
public class StoreKeywords implements Serializable {

	/**
	 * 
	 */
	private static final long serialVersionUID = 1L;

	private Integer storeId;
	private String keyWords;
	private String publicStoreKey;

	public Integer getStoreId() {
		return storeId;
	}

	public void setStoreId(Integer storeId) {
		this.storeId = storeId;
	}

	public String getKeyWords() {
		return keyWords;
	}

	public void setKeyWords(String keywords) {
		this.keyWords = keywords;
	}

	public String getPublicStoreKey() {
		return publicStoreKey;
	}

	public void setPublicStoreKey(String publicStoreKey) {
		this.publicStoreKey = publicStoreKey;
	}

}
