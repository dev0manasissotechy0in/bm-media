class ApiConstants {
  // Base URL - Change based on environment
  // static const String baseUrl = 'http://10.0.2.2'; // Android Emulator
  static const String baseUrl = 'http://192.168.1.3'; // Real Device - Wi-Fi
  // static const String baseUrl = 'http://localhost'; // Web/Desktop
  // static const String baseUrl = 'https://brackoddmedia.com'; // Production
  
  // API Endpoints
  static const String apiUrl = '$baseUrl/api';
  static const String uploadsUrl = '$baseUrl/uploads';
  
  // Specific API paths
  static const String articlesApi = '$apiUrl/articles';
  static const String categoriesApi = '$apiUrl/categories';
  static const String authApi = '$apiUrl/auth';
  static const String appPagesApi = '$apiUrl/app';
  static const String brandingApi = '$apiUrl/get-app-branding.php';
}
