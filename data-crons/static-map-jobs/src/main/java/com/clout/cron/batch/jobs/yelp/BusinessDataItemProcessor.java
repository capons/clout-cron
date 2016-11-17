package com.clout.cron.batch.jobs.yelp;

import java.util.Map;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import javax.annotation.Resource;

import org.apache.log4j.Logger;
import org.springframework.batch.item.ItemProcessor;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.context.annotation.Scope;
import org.springframework.stereotype.Component;

import com.clout.cron.batch.jobitems.BusinessItem;
import com.clout.cron.batch.jobitems.JobState;
import com.clout.cron.batch.model.Store;
import com.clout.cron.batch.model.StoreSubCategories;
import com.clout.cron.batch.storekeywords.StoreKeywordItemReader.CountryCode;
import com.clout.cron.batch.task.GoogleServiceFactory;
import com.clout.cron.batch.task.TaskException;

@Component("yelpItemProcessor")
@Scope("step")
public class BusinessDataItemProcessor implements ItemProcessor<BusinessItem, BusinessItem> { 
	private final static Logger logger = Logger.getLogger(BusinessDataItemProcessor.class);
	
	@Value("${google.cron.access.key}")
	private String accessKey;
	
	@Value("${cron.job.google.places.url}")
	private String placesUrl;

	@Value("${cron.job.google.geocode.url}")
	private String geocodeUrl;

	@Value("#{jobParameters['city']}")
	private String city;
	
	@Value("#{jobParameters['state']}")
	private String state;
	
	@Resource(name = "categoryLevel2ToNaicsMapping")
	private Map<String, Integer> categoryLevel2ToNaicsMapping;
	
	@Resource(name = "categoryLevel2Mapping")
	private Map<Integer, Integer> categoryLevel2Mapping;
	
	@Resource
	private GoogleServiceFactory googleServiceFactory;
	
	@Resource
	private JobState jobState;
	
	@Override
	public BusinessItem process(BusinessItem item) throws Exception {
		logger.info("process item " + item.getStores().size());
		
		for(Store store : item.getStores()) {
			try {
					
				String fullAddress = store.getFullAddress();
				
				/*
				 * try to convert full address to address, city, state, zip
				 */
				
				if(store.getCity() == null && store.getAddressLine1() == null && store.getZipcode() == null) {
					Pattern zipcode = Pattern.compile("[0-9]{5}");
					// find all links in page
					Matcher page = zipcode.matcher(fullAddress);
					while(page.find()){
						String zippart = page.group(0);
						store.setZipcode(zippart);
					}
					
					String[] parts = fullAddress.split(",");
					
					if(parts.length == 3) {
						store.setAddressLine1(parts[0]);
						store.setCity(parts[1]);
					}
					else {
						String[] pices = fullAddress.toUpperCase().split(city + ", " + state.toUpperCase());
						
						if(pices != null && pices.length > 1) {
							store.setAddressLine1(pices[0]);
							store.setCity(city);
						}
						else if (pices != null){
							store.setAddressLine1(pices[0]);
						}
					}
				}
				
				
				StringBuilder publicStoreKeyBuilder = new StringBuilder();
				
				publicStoreKeyBuilder.append(store.getName() == null ? "" : store.getName().replaceAll("[\\s|\\W]+", "-"));
				publicStoreKeyBuilder.append("-");
				publicStoreKeyBuilder.append(store.getAddressLine1() == null ? "" : store.getAddressLine1().replaceAll("[\\s|\\W]+", "-"));
				publicStoreKeyBuilder.append("-");
				publicStoreKeyBuilder.append(store.getAddressLine2() == null ? "" : store.getAddressLine2().replace("[\\s|\\W]+", "-"));
				publicStoreKeyBuilder.append("-");
				publicStoreKeyBuilder.append(store.getCity() == null ? "" : store.getCity().replace("([\\s|\\W]+)", "-"));
				publicStoreKeyBuilder.append("-");
				publicStoreKeyBuilder.append(state);
				publicStoreKeyBuilder.append("-");		
				publicStoreKeyBuilder.append(store.getZipcode() == null ? "" : store.getZipcode().replace("([\\s|\\W]+)", "-"));
				publicStoreKeyBuilder.append("-");
				publicStoreKeyBuilder.append(store.getCountryCode() == null ? "" : CountryCode.getFullName(store.getCountryCode()));
	
				logger.info(publicStoreKeyBuilder.toString().toLowerCase());
				
				store.setPublicStoreKey(publicStoreKeyBuilder.toString().toLowerCase());
	
				/*
				 * match the store category to the sub_category table
				 */
				Integer categoryLevel2Id = categoryLevel2ToNaicsMapping.get(item.getNaics().getCode());			
				Integer categoryId = categoryLevel2Mapping.get(categoryLevel2Id);
	
				store.setCategoryId(categoryId);
				store.setCategoryLevel2Id(categoryLevel2Id);
			}catch (Exception e) {
				e.printStackTrace();
				throw new TaskException("Unable to process " + e.getMessage());
			}
		}
		logger.info("Done " + item.getStores().size());
		return item;
	}

}
