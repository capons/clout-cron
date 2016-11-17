package com.clout.cron.batch.model;

import java.util.Date;

/**
 * @author Ung
 *
 */
public class TransactionScoreJobs extends BaseModel {

	/**
	 * 
	 */
	private static final long serialVersionUID = 1L;

	private Integer id;

	/*
	 * id of the last record processed
	 */
	private Integer lastProcessedKey;
	
	private Long lastCachedStoreKey;

	/*
	 * job status
	 */
	private Integer success;
	private Integer failed;
	private Integer rejected;
	private Integer matched;
	private Integer cached;
	private Integer newStore;

	private Date lastProcessedDate;

	public Integer getId() {
		return id;
	}

	public void setId(Integer id) {
		this.id = id;
	}

	public Integer getLastProcessedKey() {
		return lastProcessedKey;
	}

	public void setLastProcessedKey(Integer lastProcessedKey) {
		this.lastProcessedKey = lastProcessedKey;
	}

	public Long getLastCachedStoreKey() {
		return lastCachedStoreKey;
	}

	public void setLastCachedStoreKey(Long lastCachedStoreKey) {
		this.lastCachedStoreKey = lastCachedStoreKey;
	}

	public Integer getSuccess() {
		return success;
	}

	public void setSuccess(Integer success) {
		this.success = success;
	}

	public Integer getFailed() {
		return failed;
	}

	public void setFailed(Integer failed) {
		this.failed = failed;
	}

	public Integer getRejected() {
		return rejected;
	}

	public void setRejected(Integer rejected) {
		this.rejected = rejected;
	}

	public Integer getMatched() {
		return matched;
	}

	public void setMatched(Integer matched) {
		this.matched = matched;
	}

	public Integer getCached() {
		return cached;
	}

	public void setCached(Integer cached) {
		this.cached = cached;
	}

	public Integer getNewStore() {
		return newStore;
	}

	public void setNewStore(Integer newStore) {
		this.newStore = newStore;
	}

	public Date getLastProcessedDate() {
		return lastProcessedDate;
	}

	public void setLastProcessedDate(Date lastProcessedDate) {
		this.lastProcessedDate = lastProcessedDate;
	}

}
