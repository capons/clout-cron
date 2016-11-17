package com.clout.cron.batch;

public class SkippableException extends RuntimeException {

	/**
	 * 
	 */
	private static final long serialVersionUID = 1L;
	
	public SkippableException(String msg) {
		super(msg);
	}

}
