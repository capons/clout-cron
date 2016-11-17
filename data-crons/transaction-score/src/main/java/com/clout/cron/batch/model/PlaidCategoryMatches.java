package com.clout.cron.batch.model;

/**
 * 
 * @author Ung
 *
 */
public class PlaidCategoryMatches extends BaseModel {
	
	private static final long serialVersionUID = 1L;

	// plaid_sub_category_id
	private Long plaidSubCategoryId;
	
	// _clout_sub_category_id
	private Long cloutSubCategoryId;

	public Long getPlaidSubCategoryId() {
		return plaidSubCategoryId;
	}

	public void setPlaidSubCategoryId(Long plaidSubCategoryId) {
		this.plaidSubCategoryId = plaidSubCategoryId;
	}

	public Long getCloutSubCategoryId() {
		return cloutSubCategoryId;
	}

	public void setCloutSubCategoryId(Long cloutSubCategoryId) {
		this.cloutSubCategoryId = cloutSubCategoryId;
	}
	
	
}
