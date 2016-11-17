package com.clout.cron.batch.staticmap;

import java.util.HashMap;
import java.util.Map;

import javax.annotation.Resource;

import org.apache.log4j.Logger;
import org.springframework.batch.item.ItemProcessor;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.context.annotation.Scope;
import org.springframework.stereotype.Component;

import com.clout.cron.batch.CronJobException;
import com.clout.cron.batch.SkippableException;
import com.clout.cron.batch.jobitems.JobState;
import com.clout.cron.batch.jobitems.StaticMapItem;
import com.clout.cron.batch.metadata.GoogleInfo;
import com.clout.cron.batch.metadata.GoogleInfo.GoogleMetadataBuilder;
import com.clout.cron.batch.metadata.ImageInfo;
import com.clout.cron.batch.model.StaticMapStore;

/**
 * Transform store data for processing
 * 
 * @author Ung
 *
 */
@Component("staticMapItemProcessor")
@Scope("step")
public class StaticMapItemProcessor implements ItemProcessor<StaticMapStore, StaticMapItem> {
	private final static Logger logger = Logger.getLogger(StaticMapItemProcessor.class);

	@Value("${google.cron.access.key}")
	private String accessKey;

	@Value("${cron.job.google.static.map.url}")
	private String serviceEndpoint;
	
	@Value("${image.file.suffix}")
	private String suffix;
	
	@Value("${image.file.prefix}")
	private String prefix;
	
	@Resource
	private JobState jobState;

	@Override
	public StaticMapItem process(StaticMapStore store) throws Exception {
		logger.debug("In static map item processor.");
		StaticMapItem item = new StaticMapItem();
		
		try {
			Map<String, String> placeholder = new HashMap<String, String>();
			placeholder.put("\\{longitude\\}", store.getLongitude());
			placeholder.put("\\{latitude\\}", store.getLatitude());
	
			GoogleInfo googleMetadata = new GoogleMetadataBuilder().setAccessKey(accessKey)
					.setGoogleServiceEndpoint(serviceEndpoint).setPlaceholder(placeholder).execute();
			
			item.addMetadata(GoogleInfo.class.getSimpleName(), googleMetadata);
			
			ImageInfo imageMetadata = new ImageInfo();
			imageMetadata.setImageName(prefix + store.getStoreId() + suffix);
			
			item.addMetadata(ImageInfo.class.getSimpleName(), imageMetadata);
	
			item.setStoreId(store.getStoreId());
			return item;
		}catch(Exception e) {
			logger.error("Processor Error: " + e.getMessage());
			jobState.incrementFailed();
			throw new SkippableException(e.getMessage());
		}
	}

}
