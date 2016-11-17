package com.clout.cron.batch.model;

public class StoreSubCategories extends BaseModel {

	/**
	 * 
	 */
	private static final long serialVersionUID = 1L;

	private Integer id;
	private Integer categoriesId;
	private Integer storeId;

	public Integer getId() {
		return id;
	}

	public void setId(Integer id) {
		this.id = id;
	}

	public Integer getCategoriesId() {
		return categoriesId;
	}

	public void setCategoriesId(Integer categoriesId) {
		this.categoriesId = categoriesId;
	}

	public Integer getStoreId() {
		return storeId;
	}

	public void setStoreId(Integer storeId) {
		this.storeId = storeId;
	}

}
