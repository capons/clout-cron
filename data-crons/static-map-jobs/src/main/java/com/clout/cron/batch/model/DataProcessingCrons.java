package com.clout.cron.batch.model;

import java.util.Date;

/**
 * Google Map API Service cron job model
 * 
 * @author Ung
 *
 */
public class DataProcessingCrons extends BaseModel {

	/**
	 * 
	 */
	private static final long serialVersionUID = 1L;

	private Integer id;
	
	/*
	 * id of the last record processed
	 */
	private Integer lastProcessedKey;
	/*
	 * the number of items to process per job
	 */
	private Integer quota;
	/*
	 * the name of the job
	 */
	private String jobName;
	/*
	 * the API endpoint
	 */
	private String serviceEndpoint;
	
	/*
	 * job status
	 */
	private Integer success;
	private Integer failed;
	private Integer alreadyExist;
	
	private String active;
	
	private Date lastProcessedDate;

	public Integer getId() {
		return id;
	}

	public void setId(Integer id) {
		this.id = id;
	}

	public Integer getQuota() {
		return quota;
	}

	public void setQuota(Integer quota) {
		this.quota = quota;
	}

	public String getJobName() {
		return jobName;
	}

	public void setJobName(String jobName) {
		this.jobName = jobName;
	}

	public String getServiceEndpoint() {
		return serviceEndpoint;
	}

	public void setServiceEndpoint(String serviceEndpoint) {
		this.serviceEndpoint = serviceEndpoint;
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

	public Integer getAlreadyExist() {
		return alreadyExist;
	}

	public void setAlreadyExist(Integer alreadyExist) {
		this.alreadyExist = alreadyExist;
	}

	public Integer getLastProcessedKey() {
		return lastProcessedKey;
	}

	public void setLastProcessedKey(Integer lastProcessedKey) {
		this.lastProcessedKey = lastProcessedKey;
	}

	public String getActive() {
		return active;
	}

	public void setActive(String active) {
		this.active = active;
	}

	public Date getLastProcessedDate() {
		return lastProcessedDate;
	}

	public void setLastProcessedDate(Date lastProcessedDate) {
		this.lastProcessedDate = lastProcessedDate;
	}

	
}
