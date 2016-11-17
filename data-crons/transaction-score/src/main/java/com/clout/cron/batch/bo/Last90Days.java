package com.clout.cron.batch.bo;

import java.io.Serializable;

/**
 * 
 * @author Ung
 *
 */
public class Last90Days implements Serializable {

	/**
	 * 
	 */
	private static final long serialVersionUID = 1L;
	
	private String key;
	private Long date;
	private Double sum;

	public String getKey() {
		return key;
	}

	public void setKey(String key) {
		this.key = key;
	}

	public Long getDate() {
		return date;
	}

	public void setDate(Long date) {
		this.date = date;
	}

	public Double getSum() {
		return sum;
	}

	public void setSum(Double sum) {
		this.sum = sum;
	}

}
