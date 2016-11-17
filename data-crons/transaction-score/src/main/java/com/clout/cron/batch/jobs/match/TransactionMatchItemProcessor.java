package com.clout.cron.batch.jobs.match;

import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.HashSet;
import java.util.List;
import java.util.Map;
import java.util.Set;

import javax.annotation.Resource;

import org.apache.log4j.Logger;
import org.springframework.batch.item.ItemProcessor;
import org.springframework.context.annotation.Scope;
import org.springframework.data.mongodb.core.MongoTemplate;
import org.springframework.data.mongodb.core.query.Criteria;
import org.springframework.data.mongodb.core.query.Query;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.stereotype.Component;
import org.springframework.transaction.annotation.Transactional;

import com.clout.cron.batch.common.tasklet.DuplicateCheck;
import com.clout.cron.batch.jobitems.BusinessItem;
import com.clout.cron.batch.jobitems.JobState;
import com.clout.cron.batch.model.MongoStoreKeys;
import com.clout.cron.batch.model.Store;
import com.clout.cron.batch.model.TransactionSubCategories;
import com.clout.cron.batch.model.Transactions;
import com.clout.cron.batch.model.TransactionsRaw;
import com.clout.cron.batch.services.StoreMatchPatternServiceInterface;
import com.clout.cron.batch.util.AddressSanitizationUtil;
import com.clout.cron.batch.util.StringSanitizerUtil;
import com.clout.cron.specialcase.IdService;

@Component("businessDataItemProcessor")
@Scope("step")
@Transactional("readStoreTransactionManager")
public class TransactionMatchItemProcessor implements ItemProcessor<TransactionsRaw, BusinessItem> { 
	private final static Logger logger = Logger.getLogger(TransactionMatchItemProcessor.class);
	
	@Resource(name = "storeMatchPatternService")
	private StoreMatchPatternServiceInterface storeMatchPatternService;
	
	@Resource
	private MongoTemplate mongoTemplate;
	
	@Resource(name="readStoreJdbcTemplate")
	private JdbcTemplate readStoreJdbcTemplate;
	
	@Resource(name = "duplicateCheck")
	private DuplicateCheck duplicateCheck;
	
	@Resource
	private JobState jobState;
	
	@Resource(name = "idService")
	private IdService idService;
	
	@Resource(name = "plaidCategoryMatches")
	private Map<Long, Long> plaidCategoryMatches;
	

	/**
	 * @param identifier	name + address + city 	in raw form
	 * @return true if it is not a reject identifier
	 */
	private boolean isProcessable(String identifier) {
		/*
		 * if reject returns true, then return isProcessable = false
		 * if reject returns false, then return isProcesable = true
		 */
		boolean result = storeMatchPatternService.isReject(identifier);
		if(result == true) {
			return false;
		}
		return true;
	}

	@Override
	public BusinessItem process(TransactionsRaw item) throws Exception {
		logger.info("process item ");
		BusinessItem businessItem = new BusinessItem();
		
		businessItem.setRaw(item);
		
		logger.info("Payee Name " + item.getPayeeName() + " transactions_raw processed " + item.getId().intValue());
		
		/**
		 * convert it to something that can be matched with the cache name
		 */
		
		/*
		 * how about cleaning the name first and use exact match for names that cleaning can't handle.  Cleaning
		 * will reduce the manual work required to create exact match records
		 */
		String specialTranStoreName = StringSanitizerUtil.normalizeAndSanitize(item.getPayeeName().toLowerCase());
		
		String patternMatchingIdentifier = item.getPayeeName().trim().toLowerCase()+item.getAddress().trim().toLowerCase()+item.getCity().trim().toLowerCase();

		if(isProcessable(patternMatchingIdentifier) == false) {
			jobState.incrementRejected();
			businessItem.setTransactions(copy(item, null, 0, "unqualified"));
			logger.info("skipping transactions_raw " + item.getPayeeName() + ".  No store will be created.");
			return businessItem;
		}

		Long storeId = storeMatchPatternService.findExactMatch(patternMatchingIdentifier);
		
		int percentageMatch = 0;	
		String matchStatus = null;
		
		String key = specialTranStoreName;

		if(storeId != null) {
			logger.info("Found exact match for store " + patternMatchingIdentifier + " ");
			jobState.incrementMatched();
			businessItem.setTransactions(copy(item, storeId, 100, "exact-match"));
		}
		else {
			
			logger.info("looking up store id for " + item.getId());
			
			matchStatus = "name";
			if(item.getZipcode() != null && !item.getZipcode().isEmpty()) {
				key += "," + StringSanitizerUtil.sanitize(item.getZipcode());
				matchStatus += "-zipcode";
			}
			
			if(item.getCity() != null && !item.getCity().isEmpty()) {
				key += "," + StringSanitizerUtil.sanitize(item.getCity());
				matchStatus += "-city";
			}
			
			if(item.getState() != null && !item.getState().isEmpty()) {
				key += "," + StringSanitizerUtil.sanitize(item.getState());
				matchStatus += "-state";
			}
			
			if(item.getAddress() != null && !item.getAddress().isEmpty()) {
				key += "," + AddressSanitizationUtil.normalizeAddress(StringSanitizerUtil.sanitize(item.getAddress()));
				matchStatus += "-address";
			}
			
			key = key.toLowerCase();
			
			String storeIdString = findFromMongo(key);
			storeId = storeIdString == null ? null : Long.parseLong(storeIdString);
			percentageMatch = 50;
			
			if(storeId != null) {
				matchStatus = "auto-match";
			}
			
			/*
			 * store is new
			 */
			if(storeId == null) {
				logger.info("No match found in store table.  Creating store for business " + specialTranStoreName);
				
				/*
				 * Look up local cache if it is a new store and was created in this job run
				 */
				if(duplicateCheck.isDuplicate(jobState, key) == true) {
					synchronized(jobState) {
						storeId = jobState.getDupByStoreIds().get(key) == null ? null : Long.parseLong(jobState.getDupByStoreIds().get(key));
						logger.info("Found digital store id from CACHE " + storeId + " for store " + key);
					}
				}
				
				// store is not a duplicate store already processed and marked to be created then create the store
				if(storeId == null) {
					storeId = idService.getLastStoreId();
					logger.info("Creating new store for " + key + " with store id " + storeId);
					Store store = new Store();
					store.setStoreId(storeId);
					store.setChainId(0);
					store.setName(StringSanitizerUtil.normalizeAndSanitize(item.getPayeeName()));
					store.setAddressLine1(item.getAddress());
					store.setCity(item.getCity());
					store.setState(item.getState());
					store.setZipcode(item.getZipcode());
					store.setPhoneNumber(0);
					store.setStatus("pending");
					store.setKeyWords(item.getCategoryName());
					store.setCloutId(0);
					store.setStateId(0);
					store.setOnlineOnly("N");
					store.setHasMultipleLocations("N");
					store.setEmailAddress("");
					store.setStoreOwnerId(0);
					store.setLogoUrl("");
					store.setSlogan("");
					store.setSmallCoverImage("");
					store.setPrimaryContactId(0);
					store.setWebsite(item.getWebsite());
					store.setStarRating(0);
					store.setPriceRange(0.0);
					store.setIsFranchise("N");
					store.setEnteredBy(0);
					store.setLastUpdatedBy(0);
					store.setLongitude("");
					store.setLatitude("");
					store.setAddressLine2("");
					store.setCountryCode("");
					getPublicStoreKey(store);
					

					logger.info("Added to dup check " + key + " store id " + storeId);
					jobState.getDupByStoreIds().put(key, storeId.toString());	

					jobState.incrementNewStore();
					businessItem.setStore(store);
				}

				// I would think its 100 % confidence but Al's doc says 50 % confidence
				businessItem.setTransactions(copy(item, storeId, percentageMatch, matchStatus));					
			}
			else {		
				logger.info("STORE EXIST ALREADY SKIPPING store " + specialTranStoreName);
				jobState.incrementCached();
				businessItem.setTransactions(copy(item, storeId, percentageMatch, "auto_matched"));
			}
		}
			
		businessItem.setCategories(createTransactionSubCategories(businessItem.getTransactions().getId().longValue(), item.getSubCategoryId()));
		businessItem.setUpdateStore(false);					 

		return businessItem;
	}
	
	public void getPublicStoreKey(Store store) {
		if(store.getPublicStoreKey() != null && !store.getPublicStoreKey().isEmpty()) {
			return;
		}
		
		StringBuilder builder = new StringBuilder(store.getName());
		builder.append("=");
		builder.append(store.getAddressLine1());
		builder.append("-");
		builder.append(store.getAddressLine2());
		builder.append("-");
		builder.append(store.getCity());
		builder.append("-");
		builder.append(store.getState());
		builder.append("-");		
		builder.append(store.getZipcode());
		builder.append("-");
		builder.append(store.getCountryCode());
		
		store.setPublicStoreKey(builder.toString());
	}
	
	private Transactions copy(TransactionsRaw raw, Long storeId, int confidenceLevel, String matchStatus) {
		SimpleDateFormat format = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
		
		Long transactionId = idService.getLastTransactionsId();
		
		logger.info("Assigning transaction ID " + transactionId + " to store " + storeId);
		
		Transactions transaction = new Transactions();
		transaction.setId(transactionId);
		transaction.setStoreId(storeId == null ? null : storeId);
		transaction.setAddress(raw.getAddress());
		transaction.setAmount(raw.getAmount());
		transaction.setBankId(raw.getBankId());
		transaction.setCity(raw.getCity());
		
		transaction.setConfidenceLevel(confidenceLevel);
		
		transaction.setContactTelephone(raw.getContactTelephone());
		transaction.setItemCategory(raw.getCategoryName());

		transaction.setPlaceType(raw.getPlaceType());
		transaction.setRawId(raw.getId());
		transaction.setRawStoreName(raw.getPayeeName());
		transaction.setState(raw.getState());

		transaction.setTransactionType("buy");
		if(raw.getAmount() < 0) {
			transaction.setTransactionType("deposit");
		}
		
		transaction.setUserId(raw.getUserId());
		
		transaction.setIsSecurityRisk("N");

		transaction.setStatus("complete");
		if(raw.getPending().equals("true")){
			transaction.setStatus("pending");
		}
		
		transaction.setMatchStatus(matchStatus);
		
		transaction.setStartDate(raw.getPostedDate());
		
		// not sure if these are set correctly
		transaction.setEndDate(new Date());
		transaction.setItemValue(0f);
		transaction.setTransactionTax(0.0);
		transaction.setLatitude("");
		transaction.setLongitude("");
		transaction.setZipcode(raw.getZipcode());
		transaction.setItemCategory("");
		transaction.setWebsite("");
		transaction.setTransactionDescription("");
		
		return transaction;
	}
	
	private TransactionSubCategories createTransactionSubCategories(Long transactionId, String plaidCategoryId) {
		if(plaidCategoryId != null && !plaidCategoryId.isEmpty()) {
			logger.info("Plaid Category: " + plaidCategoryId);
			Long subCategoriesId = plaidCategoryMatches.get(Long.parseLong(plaidCategoryId));
			TransactionSubCategories subCategories = new TransactionSubCategories();
			subCategories.setTransactionId(transactionId);
			subCategories.setSubCategoryId(subCategoriesId);
			subCategories.setIdProcessed("N");
			return subCategories;
		}
		return null;
	}
	
	private String findFromMongo(String key) {
		Query query = new Query();
		query.addCriteria(Criteria.where("name").is(key));
		/*
		 * due to keying by name without address potential false positive
		 * "amazon" will return "amazonas deli" and amazon store will never get created
		 * 
		 */
				//regex(key));
		
		List<MongoStoreKeys> keys = mongoTemplate.find(query, MongoStoreKeys.class, "store_keys");
		
		if(keys != null && !keys.isEmpty()) {
			logger.info("Found from mongo keys : " + keys.size());
			if(keys.get(0) != null) {
				return keys.get(0).getStoreId();
			}
		}
		
		logger.info("Key from mongo is null");
		return null;
	}

}
