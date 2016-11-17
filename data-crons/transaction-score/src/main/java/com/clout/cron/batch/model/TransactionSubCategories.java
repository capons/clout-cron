package com.clout.cron.batch.model;

/**
 * 
 * @author Ung
 *
 */
public class TransactionSubCategories extends BaseModel {
	/**
	 * 
	 */
	private static final long serialVersionUID = 1L;

	private Long transactionId;
	private Long subCategoryId;
	private String idProcessed;

	public Long getTransactionId() {
		return transactionId;
	}

	public void setTransactionId(Long transactionId) {
		this.transactionId = transactionId;
	}

	public Long getSubCategoryId() {
		return subCategoryId;
	}

	public void setSubCategoryId(Long subCategoryId) {
		this.subCategoryId = subCategoryId;
	}

	public String getIdProcessed() {
		return idProcessed;
	}

	public void setIdProcessed(String idProcessed) {
		this.idProcessed = idProcessed;
	}

}
