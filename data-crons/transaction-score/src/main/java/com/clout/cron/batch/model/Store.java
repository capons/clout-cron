package com.clout.cron.batch.model;

import java.util.Date;

/**
 * store model
 * 
 * @author Ung
 *
 */
public class Store extends BaseModel {

	/**
	 * 
	 */
	private static final long serialVersionUID = 1L;

	private Long storeId;
	private Integer chainId;
	private String longitude;
	private String latitude;
	private String name;
	private String addressLine1;
	private String addressLine2;
	private String city;
	private String state;
	private String zipcode;
	private String countryCode;
	private String website;
	private Integer phoneNumber;

	/*
	 * For mongo use
	 */
	private String keyWords;
	private String publicStoreKey;

	private Integer cloutId;
	private Date startDate;
	private Integer enteredBy;
	private Date lastUpdated;
	private Integer lastUpdatedBy;
	private String isFranchise;
	private Double priceRange;
	private Integer starRating;
	private Integer primaryContactId;
	private String onlineOnly;
	private String status;
	private String hasMultipleLocations;
	private Integer stateId;
	private String emailAddress;
	private Integer storeOwnerId;
	private String logoUrl;
	private String slogan;
	private String smallCoverImage;

	private String fullAddress;

	private Integer categoryId;
	private Integer categoryLevel2Id;

	public Long getStoreId() {
		return storeId;
	}

	public void setStoreId(Long storeId) {
		this.storeId = storeId;
	}

	public String getLongitude() {
		return longitude;
	}

	public void setLongitude(String longitude) {
		this.longitude = longitude;
	}

	public String getLatitude() {
		return latitude;
	}

	public void setLatitude(String latitude) {
		this.latitude = latitude;
	}

	public String getName() {
		return name;
	}

	public void setName(String name) {
		this.name = name;
	}

	public String getAddressLine1() {
		return addressLine1;
	}

	public void setAddressLine1(String addressLine1) {
		this.addressLine1 = addressLine1;
	}

	public String getAddressLine2() {
		return addressLine2;
	}

	public void setAddressLine2(String addressLine2) {
		this.addressLine2 = addressLine2;
	}

	public String getCity() {
		return city;
	}

	public void setCity(String city) {
		this.city = city;
	}

	public String getState() {
		return state;
	}

	public void setState(String state) {
		this.state = state;
	}

	public String getZipcode() {
		return zipcode;
	}

	public void setZipcode(String zipcode) {
		this.zipcode = zipcode;
	}

	public String getCountryCode() {
		return countryCode;
	}

	public void setCountryCode(String countryCode) {
		this.countryCode = countryCode;
	}

	public String getWebsite() {
		return website;
	}

	public void setWebsite(String website) {
		this.website = website;
	}

	public Integer getPhoneNumber() {
		return phoneNumber;
	}

	public void setPhoneNumber(Integer phoneNumber) {
		this.phoneNumber = phoneNumber;
	}

	public String getKeyWords() {
		return keyWords;
	}

	public void setKeyWords(String keyWords) {
		this.keyWords = keyWords;
	}

	public String getPublicStoreKey() {
		return publicStoreKey;
	}

	public void setPublicStoreKey(String publicStoreKey) {
		this.publicStoreKey = publicStoreKey;
	}

	public Integer getCloutId() {
		return cloutId;
	}

	public void setCloutId(Integer cloutId) {
		this.cloutId = cloutId;
	}

	public Date getStartDate() {
		return startDate;
	}

	public void setStartDate(Date startDate) {
		this.startDate = startDate;
	}

	public Integer getEnteredBy() {
		return enteredBy;
	}

	public void setEnteredBy(Integer enteredBy) {
		this.enteredBy = enteredBy;
	}

	public Date getLastUpdated() {
		return lastUpdated;
	}

	public void setLastUpdated(Date lastUpdated) {
		this.lastUpdated = lastUpdated;
	}

	public Integer getLastUpdatedBy() {
		return lastUpdatedBy;
	}

	public void setLastUpdatedBy(Integer lastUpdatedBy) {
		this.lastUpdatedBy = lastUpdatedBy;
	}

	public String getIsFranchise() {
		return isFranchise;
	}

	public void setIsFranchise(String isFranchise) {
		this.isFranchise = isFranchise;
	}

	public Double getPriceRange() {
		return priceRange;
	}

	public void setPriceRange(Double priceRange) {
		this.priceRange = priceRange;
	}

	public Integer getStarRating() {
		return starRating;
	}

	public void setStarRating(Integer starRating) {
		this.starRating = starRating;
	}

	public Integer getPrimaryContactId() {
		return primaryContactId;
	}

	public void setPrimaryContactId(Integer primaryContactId) {
		this.primaryContactId = primaryContactId;
	}

	public String getOnlineOnly() {
		return onlineOnly;
	}

	public void setOnlineOnly(String onlineOnly) {
		this.onlineOnly = onlineOnly;
	}

	public String getStatus() {
		return status;
	}

	public void setStatus(String status) {
		this.status = status;
	}

	public String getHasMultipleLocations() {
		return hasMultipleLocations;
	}

	public void setHasMultipleLocations(String hasMultipleLocations) {
		this.hasMultipleLocations = hasMultipleLocations;
	}

	public Integer getStateId() {
		return stateId;
	}

	public void setStateId(Integer stateId) {
		this.stateId = stateId;
	}

	public String getEmailAddress() {
		return emailAddress;
	}

	public void setEmailAddress(String emailAddress) {
		this.emailAddress = emailAddress;
	}

	public Integer getStoreOwnerId() {
		return storeOwnerId;
	}

	public void setStoreOwnerId(Integer storeOwnerId) {
		this.storeOwnerId = storeOwnerId;
	}

	public String getLogoUrl() {
		return logoUrl;
	}

	public void setLogoUrl(String logoUrl) {
		this.logoUrl = logoUrl;
	}

	public String getSlogan() {
		return slogan;
	}

	public void setSlogan(String slogan) {
		this.slogan = slogan;
	}

	public String getSmallCoverImage() {
		return smallCoverImage;
	}

	public void setSmallCoverImage(String smallCoverImage) {
		this.smallCoverImage = smallCoverImage;
	}

	public String getFullAddress() {
		return fullAddress;
	}

	public void setFullAddress(String fullAddress) {
		this.fullAddress = fullAddress;
	}

	public Integer getCategoryId() {
		return categoryId;
	}

	public void setCategoryId(Integer categoryId) {
		this.categoryId = categoryId;
	}

	public Integer getCategoryLevel2Id() {
		return categoryLevel2Id;
	}

	public void setCategoryLevel2Id(Integer categoryLevel2Id) {
		this.categoryLevel2Id = categoryLevel2Id;
	}

	public Integer getChainId() {
		return chainId;
	}

	public void setChainId(Integer chainId) {
		this.chainId = chainId;
	}

}
