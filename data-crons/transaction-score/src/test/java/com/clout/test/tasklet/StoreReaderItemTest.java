package com.clout.test.tasklet;

import java.util.List;

import javax.annotation.Resource;

import org.apache.log4j.Logger;
import org.junit.Assert;
import org.junit.Test;
import org.junit.runner.RunWith;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.test.context.ContextConfiguration;
import org.springframework.test.context.junit4.SpringJUnit4ClassRunner;
import org.springframework.transaction.annotation.Transactional;

import com.clout.cron.batch.mapper.StoreMapper;
import com.clout.cron.batch.model.Store;


@RunWith(SpringJUnit4ClassRunner.class)
@ContextConfiguration({"classpath:/spring/batch/jobs/common-context.xml",  "classpath:/spring/batch/database.xml", "classpath:/spring/batch/jobs/cache-stores.xml"})
public class StoreReaderItemTest {
	
	private final static Logger logger = Logger.getLogger(StoreReaderItemTest.class);

	
	@Resource(name = "readStoreJdbcTemplate")
	private JdbcTemplate readStoreJdbcTemplate;

	
	@Test
	@Transactional("readStoreTransactionManager")
	public void testGeocodeParsing() {
		//
		
		String sql = "SELECT id, latitude, longitude, name, address_line_1, "
				+ "address_line_2, city, state, zipcode, _country_code, phone_number, "
				+ "website, public_store_key, key_words FROM stores "
				+ "WHERE id >= 1 AND id <= 3515";
		
		List<Store> stores = readStoreJdbcTemplate.query(sql, new StoreMapper(), new Object[]{});
		
		Assert.assertNotNull(stores);
		Assert.assertFalse(stores.isEmpty());
		
		Assert.assertEquals(3515, stores.size());
		
	}
}
