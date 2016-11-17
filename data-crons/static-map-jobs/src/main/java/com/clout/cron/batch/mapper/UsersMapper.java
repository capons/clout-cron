package com.clout.cron.batch.mapper;

import java.sql.ResultSet;
import java.sql.SQLException;

import org.springframework.jdbc.core.RowMapper;

import com.clout.cron.batch.model.Store;
import com.clout.cron.batch.model.Users;

/**
 * 
 * @author Ung
 *
 */
public class UsersMapper implements RowMapper<Users>  {

	@Override
	public Users mapRow(ResultSet rs, int row) throws SQLException {
		Users user = new Users();
		user.setId(rs.getInt("id"));
		user.setCloutId(rs.getString("clout_id"));
		return user;
	}

}
