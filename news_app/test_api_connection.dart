import 'dart:convert';
import 'package:http/http.dart' as http;

void main() async {
  const String baseUrl = 'http://192.168.1.3';
  const String apiUrl = '$baseUrl/api';
  
  print('Testing Video Articles API...');
  await testVideoAPI(apiUrl);
  
  print('\nTesting Podcast Articles API...');
  await testPodcastAPI(apiUrl);
}

Future<void> testVideoAPI(String apiUrl) async {
  try {
    final response = await http.get(
      Uri.parse('$apiUrl/articles/videos.php?limit=20&page=1'),
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    );

    print('Status Code: ${response.statusCode}');
    print('Response: ${response.body}');
    
    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      print('Success: ${data['success']}');
      print('Count: ${data['count']}');
      if (data['articles'] != null) {
        print('Articles: ${data['articles'].length}');
        for (var article in data['articles']) {
          print('  - ID: ${article['id']}, Title: ${article['title']}');
        }
      }
    }
  } catch (e) {
    print('Error: $e');
  }
}

Future<void> testPodcastAPI(String apiUrl) async {
  try {
    final response = await http.get(
      Uri.parse('$apiUrl/articles/podcasts.php?limit=20&page=1'),
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    );

    print('Status Code: ${response.statusCode}');
    print('Response: ${response.body}');
    
    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      print('Success: ${data['success']}');
      print('Count: ${data['count']}');
      if (data['articles'] != null) {
        print('Articles: ${data['articles'].length}');
        for (var article in data['articles']) {
          print('  - ID: ${article['id']}, Title: ${article['title']}');
        }
      }
    }
  } catch (e) {
    print('Error: $e');
  }
}
