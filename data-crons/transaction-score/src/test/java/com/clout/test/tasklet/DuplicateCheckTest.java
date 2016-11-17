package com.clout.test.tasklet;

import org.junit.Assert;
import org.junit.Test;

import com.clout.cron.batch.common.tasklet.DuplicateCheck;
import com.clout.cron.batch.jobitems.JobState;


public class DuplicateCheckTest {
	
	private DuplicateCheck check = new DuplicateCheck();
	
	private JobState state = new JobState();
	
	@Test
	public void testDuplicateCheck() {
		state.getDupByStoreIds().put("subway", "3815");
		state.getDupByStoreIds().put("subway=glendale galleria,glendale,ca,91204", "2211");
		
		boolean result = check.isDuplicate(state, "subway");
		Assert.assertEquals(true, result);
		
		Long storeId = state.getDupByStoreIds().get("subway=glendale galleria,glendale,ca,91204") == null ? null : Long.parseLong(state.getDupByStoreIds().get("subway=glendale galleria,glendale,ca,91204"));
		System.out.println(storeId);
		Assert.assertEquals(2211L, storeId.longValue());
		
		Long storeId2 = state.getDupByStoreIds().get("subway2=glendale galleria,glendale,ca,91204") == null ? null : Long.parseLong(state.getDupByStoreIds().get("subway2=glendale galleria,glendale,ca,91204"));
		Assert.assertNull(storeId2);
	}

}
