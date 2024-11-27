const oracledb = require('oracledb');

async function connectToDatabase() {
  let connection;

  try {
    connection = await oracledb.getConnection({
      user: 'YOUR_DATABASE_USER',
      password: 'YOUR_DATABASE_PASSWORD',
      connectString: 'localhost/YOUR_DATABASE_SERVICE_NAME'
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
