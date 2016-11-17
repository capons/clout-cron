package com.clout.cron.batch.services;

public interface SumScoresServiceInterface {

	public void generateScores(String collectionName);
	
	public void generateScores(String collectionName, Integer frequency, Integer calendarDateType);
}
