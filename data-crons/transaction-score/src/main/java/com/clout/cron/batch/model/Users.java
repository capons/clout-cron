package com.clout.cron.batch.model;

/**
 * 
 * @author Ung
 *
 */
public class Users extends BaseModel {

	/**
	 * 
	 */
	private static final long serialVersionUID = 1L;

	private Integer id;
	private String cloutId;

	public Integer getId() {
		return id;
	}

	public void setId(Integer id) {
		this.id = id;
	}

	public String getCloutId() {
		return cloutId;
	}

	public void setCloutId(String cloutId) {
		this.cloutId = cloutId;
	}

}
