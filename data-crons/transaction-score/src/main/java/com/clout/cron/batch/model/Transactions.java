package com.clout.cron.batch.model;

import java.math.BigInteger;
import java.util.Date;

public class Transactions  extends BaseModel {
	
	public static enum TransactionType {
		buy, sell, bonus, clout_refund, withdrawl, deposit, other;
	}

	public static enum S {
		pending, complete, archived;
	}

	
	private Long id;
	private Integer userId;
	private Long storeId;
	private Integer chainId;
	private Integer bankId;
	private Double amount;
	private String status;
	private String transactionType;
	private Integer rawId;
	private String rawStoreName;
	private Date startDate;
	private Date endDate;
	private Float itemValue;
	private Double transactionTax;
	private String latitude;
	private String longitude;
	private String zipcode;
	private String state;
	private String city;
	private String address;
	private String itemCategory;
	private String contactTelephone;
	private String website;
	private Integer confidenceLevel;
	private String placeType;
	private String transactionDescription;
	private String isSecurityRisk; // Y/N
	private Integer relatedPromotionId;
	private String matchStatus;

	public Long getId() {
		return id;
	}

	public void setId(Long id) {
		this.id = id;
	}

	public Integer getUserId() {
		return userId;
	}

	public void setUserId(Integer userId) {
		this.userId = userId;
	}

	public Long getStoreId() {
		return storeId;
	}

	public void setStoreId(Long storeId) {
		this.storeId = storeId;
	}

	public Integer getChainId() {
		return chainId;
	}

	public void setChainId(Integer chainId) {
		this.chainId = chainId;
	}

	public Integer getBankId() {
		return bankId;
	}

	public void setBankId(Integer bankId) {
		this.bankId = bankId;
	}

	public Double getAmount() {
		return amount;
	}

	public void setAmount(Double amount) {
		this.amount = amount;
	}

	public String getStatus() {
		return status;
	}

	public void setStatus(String status) {
		this.status = status;
	}

	public String getTransactionType() {
		return transactionType;
	}

	public void setTransactionType(String transactionType) {
		this.transactionType = transactionType;
	}

	public Integer getRawId() {
		return rawId;
	}

	public void setRawId(Integer rawId) {
		this.rawId = rawId;
	}

	public String getRawStoreName() {
		return rawStoreName;
	}

	public void setRawStoreName(String rawStoreName) {
		this.rawStoreName = rawStoreName;
	}

	public Date getStartDate() {
		return startDate;
	}

	public void setStartDate(Date startDate) {
		this.startDate = startDate;
	}

	public Date getEndDate() {
		return endDate;
	}

	public void setEndDate(Date endDate) {
		this.endDate = endDate;
	}

	public Float getItemValue() {
		return itemValue;
	}

	public void setItemValue(Float itemValue) {
		this.itemValue = itemValue;
	}

	public Double getTransactionTax() {
		return transactionTax;
	}

	public void setTransactionTax(Double transactionTax) {
		this.transactionTax = transactionTax;
	}

	public String getLatitude() {
		return latitude;
	}

	public void setLatitude(String latitude) {
		this.latitude = latitude;
	}

	public String getLongitude() {
		return longitude;
	}

	public void setLongitude(String longitude) {
		this.longitude = longitude;
	}

	public String getZipcode() {
		return zipcode;
	}

	public void setZipcode(String zipcode) {
		this.zipcode = zipcode;
	}

	public String getState() {
		return state;
	}

	public void setState(String state) {
		this.state = state;
	}

	public String getCity() {
		return city;
	}

	public void setCity(String city) {
		this.city = city;
	}

	public String getAddress() {
		return address;
	}

	public void setAddress(String address) {
		this.address = address;
	}

	public String getItemCategory() {
		return itemCategory;
	}

	public void setItemCategory(String itemCategory) {
		this.itemCategory = itemCategory;
	}

	public String getContactTelephone() {
		return contactTelephone;
	}

	public void setContactTelephone(String contactTelephone) {
		this.contactTelephone = contactTelephone;
	}

	public String getWebsite() {
		return website;
	}

	public void setWebsite(String website) {
		this.website = website;
	}

	public Integer getConfidenceLevel() {
		return confidenceLevel;
	}

	public void setConfidenceLevel(Integer confidenceLevel) {
		this.confidenceLevel = confidenceLevel;
	}

	public String getPlaceType() {
		return placeType;
	}

	public void setPlaceType(String placeType) {
		this.placeType = placeType;
	}

	public String getTransactionDescription() {
		return transactionDescription;
	}

	public void setTransactionDescription(String transactionDescription) {
		this.transactionDescription = transactionDescription;
	}

	public String getIsSecurityRisk() {
		return isSecurityRisk;
	}

	public void setIsSecurityRisk(String isSecurityRisk) {
		this.isSecurityRisk = isSecurityRisk;
	}

	public Integer getRelatedPromotionId() {
		return relatedPromotionId;
	}

	public void setRelatedPromotionId(Integer relatedPromotionId) {
		this.relatedPromotionId = relatedPromotionId;
	}

	public String getMatchStatus() {
		return matchStatus;
	}

	public void setMatchStatus(String matchStatus) {
		this.matchStatus = matchStatus;
	}

}
