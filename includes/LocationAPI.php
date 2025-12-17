<?php
/**
 * Location API Helper
 * Fetches states and cities for India and USA
 */

class LocationAPI {
    private static $instance = null;
    
    // India States and Cities
    private static $indiaStates = [
        'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh',
        'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jharkhand',
        'Karnataka', 'Kerala', 'Madhya Pradesh', 'Maharashtra', 'Manipur',
        'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Punjab',
        'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura',
        'Uttar Pradesh', 'Uttarakhand', 'West Bengal',
        'Andaman and Nicobar Islands', 'Chandigarh', 'Dadra and Nagar Haveli and Daman and Diu',
        'Delhi', 'Jammu and Kashmir', 'Ladakh', 'Lakshadweep', 'Puducherry'
    ];
    
    // USA States
    private static $usaStates = [
        'Alabama', 'Alaska', 'Arizona', 'Arkansas', 'California', 'Colorado',
        'Connecticut', 'Delaware', 'Florida', 'Georgia', 'Hawaii', 'Idaho',
        'Illinois', 'Indiana', 'Iowa', 'Kansas', 'Kentucky', 'Louisiana',
        'Maine', 'Maryland', 'Massachusetts', 'Michigan', 'Minnesota',
        'Mississippi', 'Missouri', 'Montana', 'Nebraska', 'Nevada',
        'New Hampshire', 'New Jersey', 'New Mexico', 'New York',
        'North Carolina', 'North Dakota', 'Ohio', 'Oklahoma', 'Oregon',
        'Pennsylvania', 'Rhode Island', 'South Carolina', 'South Dakota',
        'Tennessee', 'Texas', 'Utah', 'Vermont', 'Virginia', 'Washington',
        'West Virginia', 'Wisconsin', 'Wyoming', 'District of Columbia'
    ];
    
    // Major cities per state (India)
    private static $indiaCities = [
        'Andhra Pradesh' => ['Visakhapatnam', 'Vijayawada', 'Guntur', 'Nellore', 'Tirupati', 'Kakinada'],
        'Arunachal Pradesh' => ['Itanagar', 'Naharlagun', 'Pasighat', 'Tawang'],
        'Assam' => ['Guwahati', 'Silchar', 'Dibrugarh', 'Jorhat', 'Nagaon', 'Tinsukia'],
        'Bihar' => ['Patna', 'Gaya', 'Bhagalpur', 'Muzaffarpur', 'Darbhanga', 'Purnia'],
        'Chhattisgarh' => ['Raipur', 'Bhilai', 'Bilaspur', 'Korba', 'Durg', 'Rajnandgaon'],
        'Goa' => ['Panaji', 'Margao', 'Vasco da Gama', 'Mapusa', 'Ponda'],
        'Gujarat' => ['Ahmedabad', 'Surat', 'Vadodara', 'Rajkot', 'Bhavnagar', 'Jamnagar', 'Gandhinagar'],
        'Haryana' => ['Faridabad', 'Gurgaon', 'Panipat', 'Ambala', 'Yamunanagar', 'Rohtak', 'Hisar'],
        'Himachal Pradesh' => ['Shimla', 'Dharamshala', 'Mandi', 'Solan', 'Kullu', 'Manali'],
        'Jharkhand' => ['Ranchi', 'Jamshedpur', 'Dhanbad', 'Bokaro', 'Hazaribagh', 'Giridih'],
        'Karnataka' => ['Bangalore', 'Mysore', 'Hubli', 'Mangalore', 'Belgaum', 'Gulbarga', 'Bellary'],
        'Kerala' => ['Thiruvananthapuram', 'Kochi', 'Kozhikode', 'Thrissur', 'Kollam', 'Kannur'],
        'Madhya Pradesh' => ['Bhopal', 'Indore', 'Jabalpur', 'Gwalior', 'Ujjain', 'Sagar', 'Ratlam'],
        'Maharashtra' => ['Mumbai', 'Pune', 'Nagpur', 'Thane', 'Nashik', 'Aurangabad', 'Solapur', 'Kolhapur'],
        'Manipur' => ['Imphal', 'Thoubal', 'Bishnupur', 'Churachandpur'],
        'Meghalaya' => ['Shillong', 'Tura', 'Nongstoin', 'Jowai'],
        'Mizoram' => ['Aizawl', 'Lunglei', 'Champhai', 'Serchhip'],
        'Nagaland' => ['Kohima', 'Dimapur', 'Mokokchung', 'Tuensang'],
        'Odisha' => ['Bhubaneswar', 'Cuttack', 'Rourkela', 'Brahmapur', 'Sambalpur', 'Puri'],
        'Punjab' => ['Ludhiana', 'Amritsar', 'Jalandhar', 'Patiala', 'Bathinda', 'Mohali'],
        'Rajasthan' => ['Jaipur', 'Jodhpur', 'Kota', 'Udaipur', 'Ajmer', 'Bikaner', 'Alwar'],
        'Sikkim' => ['Gangtok', 'Namchi', 'Gyalshing', 'Mangan'],
        'Tamil Nadu' => ['Chennai', 'Coimbatore', 'Madurai', 'Tiruchirappalli', 'Salem', 'Tirunelveli', 'Vellore'],
        'Telangana' => ['Hyderabad', 'Warangal', 'Nizamabad', 'Khammam', 'Karimnagar', 'Mahbubnagar'],
        'Tripura' => ['Agartala', 'Udaipur', 'Dharmanagar', 'Ambassa'],
        'Uttar Pradesh' => ['Lucknow', 'Kanpur', 'Ghaziabad', 'Agra', 'Varanasi', 'Meerut', 'Allahabad', 'Noida'],
        'Uttarakhand' => ['Dehradun', 'Haridwar', 'Roorkee', 'Haldwani', 'Rudrapur', 'Kashipur'],
        'West Bengal' => ['Kolkata', 'Howrah', 'Durgapur', 'Asansol', 'Siliguri', 'Darjeeling'],
        'Delhi' => ['New Delhi', 'Central Delhi', 'North Delhi', 'South Delhi', 'East Delhi', 'West Delhi'],
        'Chandigarh' => ['Chandigarh'],
        'Puducherry' => ['Puducherry', 'Karaikal', 'Mahe', 'Yanam'],
        'Jammu and Kashmir' => ['Srinagar', 'Jammu', 'Anantnag', 'Baramulla', 'Udhampur'],
        'Ladakh' => ['Leh', 'Kargil'],
        'Andaman and Nicobar Islands' => ['Port Blair', 'Car Nicobar'],
        'Dadra and Nagar Haveli and Daman and Diu' => ['Daman', 'Silvassa', 'Diu'],
        'Lakshadweep' => ['Kavaratti', 'Agatti', 'Minicoy']
    ];
    
    // Major cities per state (USA)
    private static $usaCities = [
        'Alabama' => ['Birmingham', 'Montgomery', 'Mobile', 'Huntsville', 'Tuscaloosa'],
        'Alaska' => ['Anchorage', 'Fairbanks', 'Juneau', 'Sitka', 'Ketchikan'],
        'Arizona' => ['Phoenix', 'Tucson', 'Mesa', 'Chandler', 'Scottsdale', 'Glendale'],
        'Arkansas' => ['Little Rock', 'Fort Smith', 'Fayetteville', 'Springdale', 'Jonesboro'],
        'California' => ['Los Angeles', 'San Diego', 'San Jose', 'San Francisco', 'Fresno', 'Sacramento', 'Long Beach', 'Oakland'],
        'Colorado' => ['Denver', 'Colorado Springs', 'Aurora', 'Fort Collins', 'Lakewood', 'Boulder'],
        'Connecticut' => ['Bridgeport', 'New Haven', 'Stamford', 'Hartford', 'Waterbury'],
        'Delaware' => ['Wilmington', 'Dover', 'Newark', 'Middletown', 'Smyrna'],
        'Florida' => ['Jacksonville', 'Miami', 'Tampa', 'Orlando', 'St. Petersburg', 'Hialeah', 'Tallahassee'],
        'Georgia' => ['Atlanta', 'Columbus', 'Augusta', 'Macon', 'Savannah', 'Athens'],
        'Hawaii' => ['Honolulu', 'Pearl City', 'Hilo', 'Kailua', 'Waipahu'],
        'Idaho' => ['Boise', 'Meridian', 'Nampa', 'Idaho Falls', 'Pocatello'],
        'Illinois' => ['Chicago', 'Aurora', 'Rockford', 'Joliet', 'Naperville', 'Springfield'],
        'Indiana' => ['Indianapolis', 'Fort Wayne', 'Evansville', 'South Bend', 'Carmel'],
        'Iowa' => ['Des Moines', 'Cedar Rapids', 'Davenport', 'Sioux City', 'Iowa City'],
        'Kansas' => ['Wichita', 'Overland Park', 'Kansas City', 'Topeka', 'Olathe'],
        'Kentucky' => ['Louisville', 'Lexington', 'Bowling Green', 'Owensboro', 'Covington'],
        'Louisiana' => ['New Orleans', 'Baton Rouge', 'Shreveport', 'Lafayette', 'Lake Charles'],
        'Maine' => ['Portland', 'Lewiston', 'Bangor', 'South Portland', 'Auburn'],
        'Maryland' => ['Baltimore', 'Frederick', 'Rockville', 'Gaithersburg', 'Bowie', 'Annapolis'],
        'Massachusetts' => ['Boston', 'Worcester', 'Springfield', 'Cambridge', 'Lowell', 'Brockton'],
        'Michigan' => ['Detroit', 'Grand Rapids', 'Warren', 'Sterling Heights', 'Ann Arbor', 'Lansing'],
        'Minnesota' => ['Minneapolis', 'St. Paul', 'Rochester', 'Duluth', 'Bloomington'],
        'Mississippi' => ['Jackson', 'Gulfport', 'Southaven', 'Hattiesburg', 'Biloxi'],
        'Missouri' => ['Kansas City', 'St. Louis', 'Springfield', 'Columbia', 'Independence'],
        'Montana' => ['Billings', 'Missoula', 'Great Falls', 'Bozeman', 'Helena'],
        'Nebraska' => ['Omaha', 'Lincoln', 'Bellevue', 'Grand Island', 'Kearney'],
        'Nevada' => ['Las Vegas', 'Henderson', 'Reno', 'North Las Vegas', 'Sparks'],
        'New Hampshire' => ['Manchester', 'Nashua', 'Concord', 'Derry', 'Rochester'],
        'New Jersey' => ['Newark', 'Jersey City', 'Paterson', 'Elizabeth', 'Edison', 'Trenton'],
        'New Mexico' => ['Albuquerque', 'Las Cruces', 'Rio Rancho', 'Santa Fe', 'Roswell'],
        'New York' => ['New York City', 'Buffalo', 'Rochester', 'Yonkers', 'Syracuse', 'Albany'],
        'North Carolina' => ['Charlotte', 'Raleigh', 'Greensboro', 'Durham', 'Winston-Salem', 'Fayetteville'],
        'North Dakota' => ['Fargo', 'Bismarck', 'Grand Forks', 'Minot', 'West Fargo'],
        'Ohio' => ['Columbus', 'Cleveland', 'Cincinnati', 'Toledo', 'Akron', 'Dayton'],
        'Oklahoma' => ['Oklahoma City', 'Tulsa', 'Norman', 'Broken Arrow', 'Lawton'],
        'Oregon' => ['Portland', 'Eugene', 'Salem', 'Gresham', 'Hillsboro', 'Beaverton'],
        'Pennsylvania' => ['Philadelphia', 'Pittsburgh', 'Allentown', 'Erie', 'Reading', 'Harrisburg'],
        'Rhode Island' => ['Providence', 'Warwick', 'Cranston', 'Pawtucket', 'East Providence'],
        'South Carolina' => ['Columbia', 'Charleston', 'North Charleston', 'Mount Pleasant', 'Rock Hill'],
        'South Dakota' => ['Sioux Falls', 'Rapid City', 'Aberdeen', 'Brookings', 'Watertown'],
        'Tennessee' => ['Nashville', 'Memphis', 'Knoxville', 'Chattanooga', 'Clarksville', 'Murfreesboro'],
        'Texas' => ['Houston', 'San Antonio', 'Dallas', 'Austin', 'Fort Worth', 'El Paso', 'Arlington'],
        'Utah' => ['Salt Lake City', 'West Valley City', 'Provo', 'West Jordan', 'Orem'],
        'Vermont' => ['Burlington', 'South Burlington', 'Rutland', 'Barre', 'Montpelier'],
        'Virginia' => ['Virginia Beach', 'Norfolk', 'Chesapeake', 'Richmond', 'Newport News', 'Alexandria'],
        'Washington' => ['Seattle', 'Spokane', 'Tacoma', 'Vancouver', 'Bellevue', 'Kent'],
        'West Virginia' => ['Charleston', 'Huntington', 'Morgantown', 'Parkersburg', 'Wheeling'],
        'Wisconsin' => ['Milwaukee', 'Madison', 'Green Bay', 'Kenosha', 'Racine'],
        'Wyoming' => ['Cheyenne', 'Casper', 'Laramie', 'Gillette', 'Rock Springs'],
        'District of Columbia' => ['Washington']
    ];
    
    private function __construct() {}
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get all countries
     */
    public function getCountries() {
        return ['India', 'United States'];
    }
    
    /**
     * Get states by country
     */
    public function getStates($country) {
        if ($country === 'India') {
            return self::$indiaStates;
        } elseif ($country === 'United States' || $country === 'USA') {
            return self::$usaStates;
        }
        return [];
    }
    
    /**
     * Get cities by country and state
     */
    public function getCities($country, $state) {
        if ($country === 'India') {
            return self::$indiaCities[$state] ?? [];
        } elseif ($country === 'United States' || $country === 'USA') {
            return self::$usaCities[$state] ?? [];
        }
        return [];
    }
    
    /**
     * Get location data as JSON
     */
    public function getLocationJSON() {
        return json_encode([
            'India' => self::$indiaCities,
            'United States' => self::$usaCities
        ]);
    }
}
