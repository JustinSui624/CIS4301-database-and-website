const oracledb = require('oracledb');

async function connectToDatabase() {
  let connection;

  try {
    connection = await oracledb.getConnection({
      user: '*****',
      password: '*****',
      connectString: 'orcl/oracle.ufl.edu'
    });

    console.log('Successfully connected to Oracle!');
  } catch (err) {
    console.error('Error: ', err);
  } finally {
    if (connection) {
      try {
        await connection.close();
      } catch (err) {
        console.error('Error when closing the database connection: ', err);
      }
    }
  }
}

connectToDatabase();
