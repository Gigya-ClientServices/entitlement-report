# Partner Use Report Tool
## ~~Phase 1~~

### Goal
Generate a comprehensive use report for a partner ID

### Requirements
 * Must only allow data to be accessed by authorized persons
 * Report generation must be able to be triggered and consumed from outside the front-end

#### Input(s)
 * Partner ID
 * User Key
 * User Secret

#### Output(s)
Site-by-site report for --
  * Account count
  * Last User Login
  * Last Creation
  * Summaries:
    * Aggregate of all counts -- ignoring child sites
    * Newest login
    * Newest Creation

### Logic
1. Call admin.getUserSites to return a list of all site for the user on the partner ID.
2. Loop through all the sites calling each of the following:
    * admin.getSiteConfig to determine if they are child site -- get SiteID, APIKey
    * If not a child site... call accounts.search for each of the following queries on the site:
      * Account Count -- select count(*) from accounts
      * Last Created User -- SELECT UID, created FROM accounts order by created DESC limit 1
      * Last Logged in User -- SELECT UID, created FROM accounts order by lastLogin DESC limit 1
3. Store data in arrays
4. Format the data into a grid for easy consumption

## Phase 2

### Goal
Add ability to pull a segmented monthly view of the data between certain dates (month/year pairs) which can be displayed as a graph or downloaded as a CSV

### Requirements
UI and Report generation must provide a mode to pull the segmented data and specify starting and ending dates.

#### Input(s)
  * Mode
  * Start Month/Year
  * End Month/Year

#### Output(s)
 * Segmented month-by-month report of for summarized user counts based on the aggregation of all API Keys on a partner

### Logic
 1. Check the mode of the request
 2. If summary mode is requested, perform on the following.
 3. Segment the number of API call requests from one month prior to the start data to the end date
 4. Iterate through each segment and request an accounts.search with the following query -- SELECT count(*) FROM accounts WHERE created < $endTime -- where $endTime is time block of the current Segment
 5. Calculate the delta using the value from the previous segment
 6. Store each value in a row representing the month/year, total users, and the delta
 7. Output the information as in a CSV format so that it can be exported and consumed by another source
 8. On the front end display a graph (chart.js) of the data with two views Overall and Delta

## Phase 3

### Goal
Perform database caching and retrieval of archived monthly information for report generation to minimize the number of REST API requests necessary for data that has already been retrieved.  

### Requirements
 * Cached data must have an expiration limit
 * Cache should be able to be force expired through the report generation endpoint.
 * Current month should not be cached

## Phase 4

### Goal
Real-time status updates during the report generation process

### Requirements
* Requests to the report will be generated using a UUID for the jobID.
* Client interface will listen for updated only for the UUID
* Updated status messages will be output dynamically into the "Waiting" loader
* Report Generation endpoint will also connect to the websocket interface and provide an updated status on the job as it runs.
