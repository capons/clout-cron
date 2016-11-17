package com.clout.cron.batch.storekeywords;

import java.util.HashMap;
import java.util.List;
import java.util.Map;

import javax.annotation.PostConstruct;
import javax.annotation.Resource;

import org.apache.log4j.Logger;
import org.springframework.batch.item.ItemReader;
import org.springframework.batch.item.NonTransientResourceException;
import org.springframework.batch.item.ParseException;
import org.springframework.batch.item.UnexpectedInputException;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.context.annotation.Scope;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.stereotype.Component;
import org.springframework.transaction.annotation.Isolation;
import org.springframework.transaction.annotation.Transactional;

import com.clout.cron.batch.model.KeyValuePair;
import com.clout.cron.batch.model.Store;
import com.clout.cron.batch.task.TaskException;

/**
 * 
 * @author Ung
 *
 */
@Component("storeKeywordItemReader")
@Scope("step")
@Transactional(value="transactionManager", timeout=60000, isolation=Isolation.READ_UNCOMMITTED)
public class StoreKeywordItemReader implements ItemReader<StoreKeywords> {
	
	private final static Logger logger = Logger.getLogger(StoreKeywordItemReader.class);

	@Resource
	private ItemReader<Store> storeIdItemReader;
	
	@Resource(name = "jdbcTemplate")
	private JdbcTemplate jdbcTemplate;
	
	private Map<Integer,Integer> storesCategories = new HashMap<Integer,Integer>();
	
	@Resource(name = "categories")
	private Map<Integer, List<String>> categories;
	
	@Value("${cron.job.sitemap.store.category.mapping.query}")
	private String storeCategoryQuery;
	
	@Value("#{stepExecutionContext[fromId]}")
	private Integer fromId;
	
	@Value("#{stepExecutionContext[toId]}")
	private Integer toId;
	
	public static enum CountryCode {
		USA("united-states");
		
		private String fullName;
		
		private CountryCode(String fullName) {
			this.fullName = fullName;
		}
		
		public static String getFullName(String shortName) {
			if(USA.name().equals(shortName)) {
				return USA.fullName;
			}
			
			/*
			 * Not supported
			 */
			logger.error("Country code is not supported and must be added " + shortName);
			return "";
		}
		
		
	}
	
	
	@PostConstruct
	public void init() {

		logger.info("Categories list size " + this.categories.size() + " from Id " + fromId + " toId " + toId);

		/*
		 * get map of store id to category id
		 */
		jdbcTemplate.query(storeCategoryQuery, new Object[] {fromId, toId}, (rs, rowNum) -> {
			KeyValuePair<Integer, Integer> pair = new KeyValuePair<Integer, Integer>();
			pair.setKey(rs.getInt("_store_id"));
			pair.setValue(rs.getInt("_category_id"));
			logger.debug(" _category_id " + rs.getInt("_category_id") + " _store_id " + rs.getInt("_store_id"));
			
			return pair;
		})
		.stream()
		.forEach(pair -> storesCategories.put(pair.getKey(), pair.getValue()));		
		
		logger.debug("Store Categories list size " + this.storesCategories.size());
	}

	
	@Override
	public StoreKeywords read() throws Exception, UnexpectedInputException, ParseException, NonTransientResourceException {
		logger.info("Generating keywords and public store key");
		Store store = storeIdItemReader.read();

		if(store == null) {
			logger.info("No more items");
			return null;
		}
		
		if(store.getStoreId() == null) {
			logger.error("Store id is not valid and keywords cannot be generated");
			throw new TaskException("Store id is not valid and keywords cannot be generated");
		}
		
		StringBuilder publicStoreKeyBuilder = new StringBuilder();
		
		publicStoreKeyBuilder.append(store.getName() == null ? "" : store.getName().replaceAll("[\\s|\\W]+", "-"));
		publicStoreKeyBuilder.append("=");
		publicStoreKeyBuilder.append(store.getAddressLine1() == null ? "" : store.getAddressLine1().replaceAll("[\\s|\\W]+", "-"));
		publicStoreKeyBuilder.append("-");
		publicStoreKeyBuilder.append(store.getAddressLine2() == null ? "" : store.getAddressLine2().replace("[\\s|\\W]+", "-"));
		publicStoreKeyBuilder.append("-");
		publicStoreKeyBuilder.append(store.getCity() == null ? "" : store.getCity().replace("([\\s|\\W]+)", "-"));
		publicStoreKeyBuilder.append("-");
		publicStoreKeyBuilder.append(store.getState() == null ? "" : store.getState().replace( "([\\s|\\W]+)", "-"));
		publicStoreKeyBuilder.append("-");		
		publicStoreKeyBuilder.append(store.getZipcode() == null ? "" : store.getZipcode().replace("([\\s|\\W]+)", "-"));
		publicStoreKeyBuilder.append("-");
		publicStoreKeyBuilder.append(store.getCountryCode() == null ? "" : CountryCode.getFullName(store.getCountryCode()));
		

		logger.info(publicStoreKeyBuilder.toString().toLowerCase());
		
		Integer categoryId = this.storesCategories.get(store.getStoreId());
		
		StoreKeywords item = new StoreKeywords();
		
		item.setStoreId(store.getStoreId());
		item.setPublicStoreKey(publicStoreKeyBuilder.toString().toLowerCase());
		item.setKeyWords("");
		
		
		StringBuilder keywordBuilder = new StringBuilder();
		
		
		if(categoryId == null) {
			logger.error("Store category is not valid and keywords cannot be generated for storeId " + store.getStoreId());
			//throw new TaskException("Store category is not valid and keywords cannot be generated for storeId " + store.getStoreId());
		}
		else {
			logger.debug("CategoryId " + categoryId + " store Id " + store.getStoreId());
			List<String> categoryNames = this.categories.get(categoryId);
			
			if(categoryNames == null) {
				logger.error("Store category is not valid and keywords cannot be generated for storeId " + store.getStoreId() + " with category Id " + categoryId);
				//throw new TaskException("Store category is not valid and keywords cannot be generated for storeId " + store.getStoreId() + " with category Id " + categoryId);
			}
			else {
				logger.debug("names.size " + categoryNames.size());
	
				for(String categoryName : categoryNames) {
					keywordBuilder.append(categoryName.toLowerCase().replaceAll("-", " "));
					keywordBuilder.append(", ");
				}
				item.setKeyWords(keywordBuilder.toString().trim());
			}
		}
		
		
		logger.debug("Updating store " + item.getStoreId());
		return item;
	}

}
