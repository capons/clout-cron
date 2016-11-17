package com.clout.cron.batch.services;

public interface StoreMatchPatternServiceInterface {
	
	public void preloadMatcher();
	
	public Long findExactMatch(String storeIdentifier);
	
	public boolean isReject(String storeIdentifier); 
}
