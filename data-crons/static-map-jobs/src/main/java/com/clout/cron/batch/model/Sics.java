package com.clout.cron.batch.model;

public class Sics extends BaseModel {

	/**
	 * 
	 */
	private static final long serialVersionUID = 1L;

	private Integer id;
	private String codeDetails;

	private String subCategoryCode;
	private String code;
	private String mainCategoryCode;

	private String subCategoryCode6;
	private String subCategoryCode8;

	public Integer getId() {
		return id;
	}

	public void setId(Integer id) {
		this.id = id;
	}

	public String getCodeDetails() {
		return codeDetails;
	}

	public void setCodeDetails(String codeDetails) {
		this.codeDetails = codeDetails;
	}

	public String getSubCategoryCode() {
		return subCategoryCode;
	}

	public void setSubCategoryCode(String subCategoryCode) {
		this.subCategoryCode = subCategoryCode;
	}

	public String getCode() {
		return code;
	}

	public void setCode(String code) {
		this.code = code;
	}

	public String getMainCategoryCode() {
		return mainCategoryCode;
	}

	public void setMainCategoryCode(String mainCategoryCode) {
		this.mainCategoryCode = mainCategoryCode;
	}

	public String getSubCategoryCode6() {
		return subCategoryCode6;
	}

	public void setSubCategoryCode6(String subCategoryCode6) {
		this.subCategoryCode6 = subCategoryCode6;
	}

	public String getSubCategoryCode8() {
		return subCategoryCode8;
	}

	public void setSubCategoryCode8(String subCategoryCode8) {
		this.subCategoryCode8 = subCategoryCode8;
	}

}
