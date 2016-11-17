package com.clout.cron.batch.services;

import java.util.List;
import java.util.Map;
import java.util.concurrent.ConcurrentHashMap;
import java.util.concurrent.CopyOnWriteArrayList;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import javax.annotation.Resource;

import org.apache.log4j.Logger;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

@Service("storeMatchPatternService")
@Transactional("readStoreTransactionManager")
public class StoreMatchPatternService implements StoreMatchPatternServiceInterface {
	private final static Logger logger = Logger.getLogger(StoreMatchPatternService.class);

	@Resource(name = "readStoreJdbcTemplate")
	private JdbcTemplate readStoreJdbcTemplate;
	
	private final Map<Long, String> exactMatchPattern = new ConcurrentHashMap<Long, String>();
	
	private final List<String> rejectPattern = new CopyOnWriteArrayList<String>();
	
	public void preloadMatcher() {
		readStoreJdbcTemplate.query("SELECT * FROM store_match_patterns", (rs, rowNum) -> {
			String pattern = rs.getString("name_pattern") + rs.getString("address_pattern") + rs.getString("city_pattern");
			if("reject".equals(rs.getString("command"))) {
				rejectPattern.add(pattern.trim());
			}else if("match".equals(rs.getString("command"))) {
				exactMatchPattern.put(rs.getLong("match_store_id"), pattern.trim());
			}
			
			return null;
		});
		
		logger.info("Rejects");
		rejectPattern.stream().forEach(logger::info);
		logger.info("Exact Matches");
		exactMatchPattern.values().stream().forEach(logger::info);
	}
	
	public synchronized Long findExactMatch(String storeIdentifier) {
		Long start = System.currentTimeMillis();

		for(Map.Entry<Long, String> entry : exactMatchPattern.entrySet()) {
		    Pattern pattern = Pattern.compile(entry.getValue());
		    Matcher m = pattern.matcher(storeIdentifier);
			if(true == m.matches()) {
				logger.info("Match found " + (System.currentTimeMillis() - start) + " for store " + storeIdentifier + " found match");				    
				return entry.getKey();
			}
		}
		
	    logger.info("Match not found " + (System.currentTimeMillis() - start) + " for store " + storeIdentifier + " found no match");
	    
	    return null;
	}
	
	public synchronized boolean isReject(String storeIdentifier) {
		Long start = System.currentTimeMillis();

		for(String matcher : rejectPattern) {
		    Pattern pattern = Pattern.compile(matcher);
		    Matcher m = pattern.matcher(storeIdentifier);
			if(true == m.matches()) {
				logger.info("Reject found " + (System.currentTimeMillis() - start) + " for store " + storeIdentifier + " found reject");
				return true;
			}
		}
		
	    logger.info("Reject not found " + (System.currentTimeMillis() - start) + " for store " + storeIdentifier + " found no match");
	    
	    return false;
	}
}
