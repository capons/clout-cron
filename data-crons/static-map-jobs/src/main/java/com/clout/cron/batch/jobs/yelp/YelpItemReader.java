package com.clout.cron.batch.jobs.yelp;

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

import com.clout.cron.batch.jobitems.JobState;
import com.clout.cron.batch.jobitems.StoresItem;
import com.clout.cron.batch.metadata.YellowPageInfo;
import com.clout.cron.batch.metadata.YellowPageInfo.YellowPageMetadataBuilder;
import com.clout.cron.batch.model.Store;
import com.clout.cron.batch.model.YellowpagesLink;
import com.clout.cron.batch.task.TaskException;
import com.clout.cron.batch.task.YellowPagesTask;

/**
 * Use category to retrieve business name and address from yellowpage
 * 
 * categories_level_2_naics_mapping; maps naics_code to _category_level_2_id - put memcache in front of this to speed up the job
 * 
 * if(categories_level_2_naics_mapping._category_level_2_id = category_level_2.id) then
 * create store_sub_categories with _category_id, _store_id
 * 
 * 
 * @author Ung
 *
 */
@Component("yelp2ItemReader")
@Scope("step")
public class YelpItemReader implements ItemReader<StoresItem> { 
	
	private final static Logger logger = Logger.getLogger(YelpItemReader.class);
	
	@Resource(name = "jdbcTemplate")
	private JdbcTemplate jdbcTemplate;

	@Value("#{stepExecutionContext[fromId]}")
	private Integer fromId;
	
	@Value("#{stepExecutionContext[toId]}")
	private Integer toId;
	
	@Resource
	private JobState jobState;
	
	@Resource
	private ItemReader<YellowpagesLink> linksItemReader;

	
	@Override
	public StoresItem read() throws Exception, UnexpectedInputException, ParseException, NonTransientResourceException, TaskException {
		YellowpagesLink link = linksItemReader.read();
		if(link == null) {
			logger.info("ifos is null");
			return null;
		}

		try {
			YellowPageInfo info = new YellowPageMetadataBuilder().setNaics(null).setCategory(link.getCategory()).setLocation(link.getCity() + ", " + link.getState()).setPage(link.getPage().toString()).execute(); 

			logger.info("working on " + info.getServiceEndpoint() + " for id range " + fromId + " " + toId);
			
			YellowPagesTask task = new YellowPagesTask();
			task.execute(info);
			logger.info("executed ");
			
			StoresItem items = new StoresItem();
			
			while(task.hasNext()) {
				logger.info("hasNext");
				try {
					// Update or create a store record
					Store store = new Store();
					String storeName = task.getBusinessName();
					if(storeName == null) {
						task.next();
						continue;
					}
					store.setName(storeName);
					store.setWebsite(task.getNextWebsite());
					store.setPhoneNumber(task.getNextPhones() == null ? "" : task.getNextPhones());
					store.setState(link.getState());
					store.setCity(task.getNextCity());
					store.setAddressLine1(task.getNextStreetAddress());
					store.setZipcode(task.getNextPostalCode());
					store.setFullAddress(task.getNextAddress());
					store.setCategory(link.getCategory());
					logger.info("after parsing");
	
					logger.info("store name " + store.getName() + " full address " + store.getFullAddress() + " ");
					
					items.getStores().add(store);
				}catch(Exception e){
					logger.error("Unable to extract store");
				}
				finally {
					task.next();
				}
				
			}
			logger.info("Found stores " + items.getStores().size());
			return items;
		} catch(Exception e) {
			e.printStackTrace();
			throw new TaskException("Unexpected error.  Skipping " + e.getMessage());
		}
	}

}
