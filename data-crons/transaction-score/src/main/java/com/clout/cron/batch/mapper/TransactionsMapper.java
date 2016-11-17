package com.clout.cron.batch.mapper;

import java.math.BigInteger;
import java.sql.ResultSet;
import java.sql.SQLException;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.jdbc.core.RowMapper;

import com.clout.cron.batch.model.Transactions;

public class TransactionsMapper implements RowMapper<Transactions> {

	private final static Logger logger = LoggerFactory.getLogger(TransactionsMapper.class);

	@Override
	public Transactions mapRow(ResultSet rs, int arg1) throws SQLException {
		Transactions trans = new Transactions();
		trans.setId(rs.getLong("id"));
		trans.setTransactionType(rs.getString("transaction_type"));
		trans.setUserId(rs.getInt("_user_id"));

		trans.setStoreId(rs.getLong("_store_id"));
		trans.setChainId(rs.getInt("_chain_id"));
		trans.setBankId(rs.getInt("_bank_id"));

		trans.setStatus(rs.getString("status"));

		trans.setAmount(rs.getDouble("amount"));

		trans.setRawId(rs.getInt("_raw_id"));
		trans.setRawStoreName(rs.getString("raw_store_name"));
		trans.setStartDate(rs.getDate("start_date"));
		trans.setEndDate(rs.getDate("end_date"));
		trans.setItemValue(rs.getFloat("item_value"));
		trans.setTransactionTax(rs.getDouble("transaction_tax"));
		trans.setLatitude(rs.getString("latitude"));
		trans.setLongitude(rs.getString("longitude"));
		trans.setZipcode(rs.getString("zipcode"));
		trans.setState(rs.getString("state"));
		trans.setCity(rs.getString("city"));
		trans.setAddress(rs.getString("address"));
		trans.setItemCategory(rs.getString("item_category"));
		trans.setContactTelephone(rs.getString("contact_telephone"));
		trans.setWebsite(rs.getString("website"));
		trans.setConfidenceLevel(rs.getInt("confidence_level"));
		trans.setPlaceType(rs.getString("place_type"));
		trans.setTransactionDescription(rs.getString("transaction_description"));
		trans.setIsSecurityRisk(rs.getString("is_security_risk"));
		trans.setMatchStatus(rs.getString("match_status"));
		trans.setRelatedPromotionId(rs.getInt("_related_promotion_id"));

		return trans;
	}
}
