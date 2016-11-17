package com.clout.cron.batch.jobitems;

import java.io.Serializable;
import java.util.concurrent.atomic.AtomicInteger;

import org.springframework.stereotype.Component;

/**
 * 
 * @author Ung
 *
 */
@Component("jobState")
public class JobState implements Serializable {

	private static final long serialVersionUID = 1L;
	
	private volatile Object lock = new Object();

	private volatile AtomicInteger success = new AtomicInteger(0);
	private volatile AtomicInteger failed = new AtomicInteger(0);
	private volatile AtomicInteger alreadyExist = new AtomicInteger(0);
	
	private AtomicInteger lastProcessedKey = new AtomicInteger();

	public void incrementSuccess() {
		success.incrementAndGet();
	}
	
	public void incrementFailed() {
		failed.incrementAndGet();
	}
	
	public void incrementAlreadyExist() {
		alreadyExist.incrementAndGet();
	}
	
	public void setLastProcessedKey(int lastKey) {
		synchronized(lock) {
			if(this.lastProcessedKey.get() < lastKey) {
				this.lastProcessedKey.set(lastKey);
			}
		}
	}

	public int getLastProcessedKey() {
		synchronized(lock) {
			return this.lastProcessedKey.get();
		}
	}

	public int getSuccess() {
		return success.get();
	}

	public int getFailed() {
		return failed.get();
	}

	public int getAlreadyExist() {
		return alreadyExist.get();
	}

}
