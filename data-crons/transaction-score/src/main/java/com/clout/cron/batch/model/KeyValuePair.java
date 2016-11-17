package com.clout.cron.batch.model;

import java.io.Serializable;

/**
 * 
 * @author Ung
 *
 */
public class KeyValuePair<T, S> implements Serializable {

	/**
	 * 
	 */
	private static final long serialVersionUID = 1L;

	private T key;
	
	private S value;

	public T getKey() {
		return key;
	}

	public void setKey(T key) {
		this.key = key;
	}

	public S getValue() {
		return value;
	}

	public void setValue(S value) {
		this.value = value;
	}
	
	
	
}
