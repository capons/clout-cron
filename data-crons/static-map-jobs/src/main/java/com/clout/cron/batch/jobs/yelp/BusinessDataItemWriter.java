package com.clout.cron.batch.jobs.yelp;

import java.util.ArrayList;
import java.util.List;

import javax.annotation.Resource;

import org.apache.log4j.Logger;
import org.springframework.batch.item.ItemWriter;
import org.springframework.context.annotation.Scope;
import org.springframework.stereotype.Component;

import com.clout.cron.batch.jobitems.BaseItem;
import com.clout.cron.batch.jobitems.BusinessItem;
import com.clout.cron.batch.jobitems.JobState;
import com.clout.cron.batch.model.Store;
import com.clout.cron.batch.task.TaskException;

/**
 * ALERT: This is not used due to only need to insert directly to DB
 * 
 * 
 * retrieve store by name
 * retrieve store category by category store id
 * 
 * if duplicate log and skip
 * 
 * retrieve business location with geocode
 * retrieve business data with google places API
 * 
 * @author Ung
 *
 */
@Component("yelpItemWriter")
@Scope("step")
public class BusinessDataItemWriter implements ItemWriter<BaseItem>{
	private final static Logger logger = Logger.getLogger(BusinessDataItemWriter.class);
	
	@Resource(name = "insertStoreItemWriter")
	private ItemWriter<Store> insertStoreItemWriter;

	@Resource
	private JobState jobState;
	

	@Override
	public void write(List<? extends BaseItem> items) throws Exception {
		logger.info("items to write " + items.size());

		List<Store> stores = new ArrayList<Store>();
		
		for(int i = 0; i < items.size(); i ++) {
			BusinessItem item = ((BusinessItem)items.get(i));
		
			stores.addAll(item.getStores());
		}
		
		try {
			insertStoreItemWriter.write(stores);
			
			for(int i = 0; i < items.size(); i ++) {
				synchronized(jobState) {
					jobState.incrementSuccess();
				}
			}
		}catch(Exception e) {
			e.printStackTrace();
			throw new TaskException("Unexpected error skipping " + e.getMessage());
		}
		
		logger.info("Done");
	}
}
