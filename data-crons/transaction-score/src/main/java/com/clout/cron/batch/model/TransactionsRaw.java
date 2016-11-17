package com.clout.cron.batch.model;

import java.math.BigInteger;
import java.util.Date;

public class TransactionsRaw  extends BaseModel {

	/**
	 * 
	 */
	private static final long serialVersionUID = 1L;
	
	private Integer id;
	private String transactionId;
	private String transactionType;
	private String currencyType;
	private String institutionTransactionId;
	private String correctInstitutionTransactionId;
	private String correctAction;
	private String serverTransactionId;
	private String checkNumber;
	private String referenceNumber;
	private String confirmationNumber;
	private String payeeId;
	private String payeeName;
	private String extendedPayeeName;
	private String memo;
	private String type;
	private String valueType;
	private String currencyRate;
	private String originalCurrency;
	private Date postedDate;
	private String userDate;
	private Date availableDate;
	private Double amount;
	private Double runningBalanceAmount;
	private String pending;
	private String normalizedPayeeName;
	private String merchant;
	private String sic;
	private String source;
	private String categoryName;
	private String contextType;
	private String scheduleC;
	private String cloutTransactionId;
	private String latitude;
	private String longitude;
	private String zipcode;
	private String state;
	private String city;
	private String address;
	private String subCategoryId;
	private String contactTelephone;
	private String website;
	private Float confidenceLevel;
	private String placeType;
	private String relatedAdId;
	private Integer userId;
	private Integer bankId;
	private String apiAccount;
	private String bankingTransactionType;
	private String subaccountFundType;
	private String banking401KSourceType;
	private Double principalAmount;
	private Double interestAmount;
	private Double escrowTotalAmount;
	private Double escrowTaxAmount;
	private Double escrowInsuranceAmount;
	private Double escrowPmiAmount;
	private Double escrowFeesAmount;
	private Double escrowOtherAmount;
	private Date lastUpdateDate;
	private String isSaved;
	private String isActive;
	private String isProcessed;

	public Integer getId() {
		return id;
	}

	public void setId(Integer id) {
		this.id = id;
	}

	public String getTransactionId() {
		return transactionId;
	}

	public void setTransactionId(String transactionId) {
		this.transactionId = transactionId;
	}

	public String getTransactionType() {
		return transactionType;
	}

	public void setTransactionType(String transactionType) {
		this.transactionType = transactionType;
	}

	public String getCurrencyType() {
		return currencyType;
	}

	public void setCurrencyType(String currencyType) {
		this.currencyType = currencyType;
	}

	public String getInstitutionTransactionId() {
		return institutionTransactionId;
	}

	public void setInstitutionTransactionId(String institutionTransactionId) {
		this.institutionTransactionId = institutionTransactionId;
	}

	public String getCorrectInstitutionTransactionId() {
		return correctInstitutionTransactionId;
	}

	public void setCorrectInstitutionTransactionId(String correctInstitutionTransactionId) {
		this.correctInstitutionTransactionId = correctInstitutionTransactionId;
	}

	public String getCorrectAction() {
		return correctAction;
	}

	public void setCorrectAction(String correctAction) {
		this.correctAction = correctAction;
	}

	public String getServerTransactionId() {
		return serverTransactionId;
	}

	public void setServerTransactionId(String serverTransactionId) {
		this.serverTransactionId = serverTransactionId;
	}

	public String getCheckNumber() {
		return checkNumber;
	}

	public void setCheckNumber(String checkNumber) {
		this.checkNumber = checkNumber;
	}

	public String getReferenceNumber() {
		return referenceNumber;
	}

	public void setReferenceNumber(String referenceNumber) {
		this.referenceNumber = referenceNumber;
	}

	public String getConfirmationNumber() {
		return confirmationNumber;
	}

	public void setConfirmationNumber(String confirmationNumber) {
		this.confirmationNumber = confirmationNumber;
	}

	public String getPayeeId() {
		return payeeId;
	}

	public void setPayeeId(String payeeId) {
		this.payeeId = payeeId;
	}

	public String getPayeeName() {
		return payeeName;
	}

	public void setPayeeName(String payeeName) {
		this.payeeName = payeeName;
	}

	public String getExtendedPayeeName() {
		return extendedPayeeName;
	}

	public void setExtendedPayeeName(String extendedPayeeName) {
		this.extendedPayeeName = extendedPayeeName;
	}

	public String getMemo() {
		return memo;
	}

	public void setMemo(String memo) {
		this.memo = memo;
	}

	public String getType() {
		return type;
	}

	public void setType(String type) {
		this.type = type;
	}

	public String getValueType() {
		return valueType;
	}

	public void setValueType(String valueType) {
		this.valueType = valueType;
	}

	public String getCurrencyRate() {
		return currencyRate;
	}

	public void setCurrencyRate(String currencyRate) {
		this.currencyRate = currencyRate;
	}

	public String getOriginalCurrency() {
		return originalCurrency;
	}

	public void setOriginalCurrency(String originalCurrency) {
		this.originalCurrency = originalCurrency;
	}

	public Date getPostedDate() {
		return postedDate;
	}

	public void setPostedDate(Date postedDate) {
		this.postedDate = postedDate;
	}

	public String getUserDate() {
		return userDate;
	}

	public void setUserDate(String userDate) {
		this.userDate = userDate;
	}

	public Date getAvailableDate() {
		return availableDate;
	}

	public void setAvailableDate(Date availableDate) {
		this.availableDate = availableDate;
	}

	public Double getAmount() {
		return amount;
	}

	public void setAmount(Double amount) {
		this.amount = amount;
	}

	public Double getRunningBalanceAmount() {
		return runningBalanceAmount;
	}

	public void setRunningBalanceAmount(Double runningBalanceAmount) {
		this.runningBalanceAmount = runningBalanceAmount;
	}

	public String getPending() {
		return pending;
	}

	public void setPending(String pending) {
		this.pending = pending;
	}

	public String getNormalizedPayeeName() {
		return normalizedPayeeName;
	}

	public void setNormalizedPayeeName(String normalizedPayeeName) {
		this.normalizedPayeeName = normalizedPayeeName;
	}

	public String getMerchant() {
		return merchant;
	}

	public void setMerchant(String merchant) {
		this.merchant = merchant;
	}

	public String getSic() {
		return sic;
	}

	public void setSic(String sic) {
		this.sic = sic;
	}

	public String getSource() {
		return source;
	}

	public void setSource(String source) {
		this.source = source;
	}

	public String getCategoryName() {
		return categoryName;
	}

	public void setCategoryName(String categoryName) {
		this.categoryName = categoryName;
	}

	public String getContextType() {
		return contextType;
	}

	public void setContextType(String contextType) {
		this.contextType = contextType;
	}

	public String getScheduleC() {
		return scheduleC;
	}

	public void setScheduleC(String scheduleC) {
		this.scheduleC = scheduleC;
	}

	public String getCloutTransactionId() {
		return cloutTransactionId;
	}

	public void setCloutTransactionId(String cloutTransactionId) {
		this.cloutTransactionId = cloutTransactionId;
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

	public String getSubCategoryId() {
		return subCategoryId;
	}

	public void setSubCategoryId(String subCategoryId) {
		this.subCategoryId = subCategoryId;
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

	public Float getConfidenceLevel() {
		return confidenceLevel;
	}

	public void setConfidenceLevel(Float confidenceLevel) {
		this.confidenceLevel = confidenceLevel;
	}

	public String getPlaceType() {
		return placeType;
	}

	public void setPlaceType(String placeType) {
		this.placeType = placeType;
	}

	public String getRelatedAdId() {
		return relatedAdId;
	}

	public void setRelatedAdId(String relatedAdId) {
		this.relatedAdId = relatedAdId;
	}

	public Integer getUserId() {
		return userId;
	}

	public void setUserId(Integer userId) {
		this.userId = userId;
	}

	public Integer getBankId() {
		return bankId;
	}

	public void setBankId(Integer bankId) {
		this.bankId = bankId;
	}

	public String getApiAccount() {
		return apiAccount;
	}

	public void setApiAccount(String apiAccount) {
		this.apiAccount = apiAccount;
	}

	public String getBankingTransactionType() {
		return bankingTransactionType;
	}

	public void setBankingTransactionType(String bankingTransactionType) {
		this.bankingTransactionType = bankingTransactionType;
	}

	public String getSubaccountFundType() {
		return subaccountFundType;
	}

	public void setSubaccountFundType(String subaccountFundType) {
		this.subaccountFundType = subaccountFundType;
	}

	public String getBanking401KSourceType() {
		return banking401KSourceType;
	}

	public void setBanking401KSourceType(String banking401kSourceType) {
		banking401KSourceType = banking401kSourceType;
	}

	public Double getPrincipalAmount() {
		return principalAmount;
	}

	public void setPrincipalAmount(Double principalAmount) {
		this.principalAmount = principalAmount;
	}

	public Double getInterestAmount() {
		return interestAmount;
	}

	public void setInterestAmount(Double interestAmount) {
		this.interestAmount = interestAmount;
	}

	public Double getEscrowTotalAmount() {
		return escrowTotalAmount;
	}

	public void setEscrowTotalAmount(Double escrowTotalAmount) {
		this.escrowTotalAmount = escrowTotalAmount;
	}

	public Double getEscrowTaxAmount() {
		return escrowTaxAmount;
	}

	public void setEscrowTaxAmount(Double escrowTaxAmount) {
		this.escrowTaxAmount = escrowTaxAmount;
	}

	public Double getEscrowInsuranceAmount() {
		return escrowInsuranceAmount;
	}

	public void setEscrowInsuranceAmount(Double escrowInsuranceAmount) {
		this.escrowInsuranceAmount = escrowInsuranceAmount;
	}

	public Double getEscrowPmiAmount() {
		return escrowPmiAmount;
	}

	public void setEscrowPmiAmount(Double escrowPmiAmount) {
		this.escrowPmiAmount = escrowPmiAmount;
	}

	public Double getEscrowFeesAmount() {
		return escrowFeesAmount;
	}

	public void setEscrowFeesAmount(Double escrowFeesAmount) {
		this.escrowFeesAmount = escrowFeesAmount;
	}

	public Double getEscrowOtherAmount() {
		return escrowOtherAmount;
	}

	public void setEscrowOtherAmount(Double escrowOtherAmount) {
		this.escrowOtherAmount = escrowOtherAmount;
	}

	public Date getLastUpdateDate() {
		return lastUpdateDate;
	}

	public void setLastUpdateDate(Date lastUpdateDate) {
		this.lastUpdateDate = lastUpdateDate;
	}

	public String getIsSaved() {
		return isSaved;
	}

	public void setIsSaved(String isSaved) {
		this.isSaved = isSaved;
	}

	public String getIsActive() {
		return isActive;
	}

	public void setIsActive(String isActive) {
		this.isActive = isActive;
	}

	public String getIsProcessed() {
		return isProcessed;
	}

	public void setIsProcessed(String isProcessed) {
		this.isProcessed = isProcessed;
	}

}
