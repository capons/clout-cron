package com.clout.cron.batch.jobs.yelp;

import java.io.File;
import java.io.FileWriter;
import java.io.IOException;
import java.io.UnsupportedEncodingException;
import java.util.ArrayList;
import java.util.List;
import java.util.Map;

import javax.annotation.Resource;

import org.apache.log4j.Logger;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.stereotype.Component;
import org.springframework.transaction.annotation.Transactional;

import com.clout.cron.batch.jobitems.JobState;
import com.clout.cron.batch.metadata.YellowPageInfo;
import com.clout.cron.batch.metadata.YellowPageInfo.YellowPageMetadataBuilder;
import com.clout.cron.batch.model.KeyValuePair;
import com.clout.cron.batch.model.Naics;
import com.clout.cron.batch.task.TaskException;
import com.clout.cron.batch.task.YellowPagesTask;

/*
 * create page with naics info
 */
@Component("yelpLoadLinkService")
public class LoadLinkService  { 
	
	private final static Logger logger = Logger.getLogger(BusinessDataItemReader.class);
	
	private int APPROX_ITEMS_PER_PAGE = 30;
	
	private String DEFAULT_PAGE = "1";
	
	@Resource(name = "jdbcTemplate")
	private JdbcTemplate jdbcTemplate;
	
	@Resource(name = "naicsCategory")
	private List<Naics> naicsCategory;
	
	@Value("${cron.job.business.data.category.query}")
	private String naicsCategoryQuery;
	
	@Value("${cron.job.business.data.naics.category.mapping.query}")
	private String naicsCategoryMappingQuery;
	
	@Resource(name = "categoryLevel2ToNaicsMapping")
	private Map<String, Integer> categoryLevel2ToNaicsMapping;
	
	@Value("${insert.yellowpages.link.table.name}")
	private String linkTableName;

	@Resource
	private JobState jobState;
	
	@Transactional
	public void load(String city, String state, Integer fromId, Integer toId) {

		logger.info("Categories list size " + this.naicsCategory.size() + " from Id " + fromId + " toId " + toId);
		
		
		StringBuilder naicsCodeList = new StringBuilder();
		
		List<String> dupList = new ArrayList<String>();

		/*
		 * get map of store id to category id
		 */
		jdbcTemplate.query(naicsCategoryQuery, new Object[] {fromId, toId}, (rs, rowNum) -> {
			Naics naics = new Naics();
			naics.setId(rs.getInt("id"));
			naics.setCodeDetails(rs.getString("code_details").trim());
			naics.setCode(rs.getString("code").trim());
			naics.setMainCategoryCode(rs.getString("main_category_code").trim());
			naics.setSubCategoryCode(rs.getString("sub_category_code").trim());
			return naics;
		})
		.stream()
		.filter(item -> {
			if(item.getId() > jobState.getLastProcessedKey()) {
				jobState.setLastProcessedKey(item.getId());
			}
			if(!dupList.contains(item.getCodeDetails().toLowerCase())) {
				logger.info("Not a dup " + item.getCodeDetails());
				return true;
			}
			return false;
		})
		.forEach(item -> {
			dupList.add(item.getCodeDetails().toLowerCase());
			naicsCategory.add(item);	
			naicsCodeList.append(item.getCode() +",");
		});
		
		logger.info(naicsCodeList.toString() + " dupList " + dupList.size());
		
		
		if(naicsCodeList != null && naicsCodeList.toString().length() > 0) {
			/*
			 * needed to create a record to map a store to a naics category via the category_level_2 table by adding an
			 * entry in store_sub_category. 
			 */
			jdbcTemplate.query(naicsCategoryMappingQuery, new Object[] {naicsCodeList.toString().substring(0, naicsCodeList.toString().length() - 1)}, (rs, rowNum) -> {
				KeyValuePair<String, Integer> pair = new KeyValuePair<String, Integer>();
				pair.setKey(rs.getString("naics_code"));
				pair.setValue(rs.getInt("_category_level_2_id"));
				return pair;
			})
			.stream()
			.forEach(item -> categoryLevel2ToNaicsMapping.put(item.getKey(), item.getValue()));	

			logger.info("naics size: " + naicsCategory.size());

			/*
			 * One category only one info
			 */
			for(Naics naic : naicsCategory) {
				int pages = buildInfos(naic, DEFAULT_PAGE, city, state);
				logger.info("Total pages " + pages);
				if(pages > 1) {
					for(int i = 2; i < pages; i ++) {
						try {
							YellowPageInfo info = new YellowPageMetadataBuilder().setNaics(naic).setCategory(naic.getCodeDetails()).setLocation(city + ", " + state).setPage(i+"").execute(); 
							jdbcTemplate.execute("INSERT INTO "+ linkTableName + " (naics_code, code_detail,link, city, state, category, page) VALUES ('" + info.getNaics().getCode() + "', '" + info.getNaics().getCodeDetails() + "','" + info.getServiceEndpoint() + "','" + city + "','" + state + "','" + naic.getCodeDetails() + "', '" + i + "');");
						} catch (UnsupportedEncodingException e) {
							logger.error(e.getMessage());
						} 
					}	
				}
				logger.info("Finished with naic " + naic.getCodeDetails());
			}
		}
		
		logger.info("Store Categories list size " + this.naicsCategory.size());
	}
	
	@Transactional
	private int buildInfos(Naics naic, String page, String city, String state) {
		try {
			YellowPageInfo info = new YellowPageMetadataBuilder().setNaics(naic).setCategory(naic.getCodeDetails()).setLocation(city + ", " + state).setPage(page).execute(); 
			
			// get the first page and determine how many results for the given category and location
			YellowPagesTask task = new YellowPagesTask();
			
			/*
			 * read the first request for business info and update infos list one time
			 */
			task.execute(info);

			int pages = task.getTotalBusinesses() / APPROX_ITEMS_PER_PAGE;
			if(task.getTotalBusinesses() % APPROX_ITEMS_PER_PAGE > 0) {
				pages ++;
			}			

			jdbcTemplate.execute("INSERT INTO " + linkTableName + " (naics_code, code_detail,link, city, state, category, page) VALUES ('" + info.getNaics().getCode() + "', '" + info.getNaics().getCodeDetails() + "','" + info.getServiceEndpoint() + "','" + city + "','" + state + "','" + naic.getCodeDetails() + "', '" + page + "');");

			/*
			 * Build infos from it
			 */
			return pages;
		} catch (UnsupportedEncodingException e) {
			logger.error(e.getMessage());
		} catch (TaskException e) {
			logger.error(e.getMessage());
		}
		return 0;
	}

}
