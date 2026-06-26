import 'dart:convert';
import 'package:http/http.dart' as http;

class MatchModel {
  final String id;
  final String homeTeam;
  final String awayTeam;
  final String homeLogo;
  final String awayLogo;
  final String status;
  final String statusText;
  final String homeScore;
  final String awayScore;
  final String time;
  final String streamUrl;
  final String channel;
  final String league;

  MatchModel({
    required this.id,
    required this.homeTeam,
    required this.awayTeam,
    required this.homeLogo,
    required this.awayLogo,
    required this.status,
    required this.statusText,
    required this.homeScore,
    required this.awayScore,
    required this.time,
    required this.streamUrl,
    required this.channel,
    required this.league,
  });

  factory MatchModel.fromJson(Map<String, dynamic> json) {
    return MatchModel(
      id: json['id'].toString(),
      homeTeam: json['homeTeam'] ?? '',
      awayTeam: json['awayTeam'] ?? '',
      homeLogo: json['homeLogo'] ?? '',
      awayLogo: json['awayLogo'] ?? '',
      status: json['status'] ?? 'upcoming',
      statusText: json['status_text'] ?? '',
      homeScore: json['homeScore'].toString(),
      awayScore: json['awayScore'].toString(),
      time: json['time'] ?? '',
      streamUrl: json['streamUrl'] ?? '',
      channel: json['channel'] ?? 'غير معروف',
      league: json['league'] ?? '',
    );
  }
}

class ApiService {
  static const String baseUrl = 'https://football-production-14a6.up.railway.app/api.php';

  Future<List<MatchModel>> fetchMatches() async {
    try {
      final response = await http.get(Uri.parse('$baseUrl?action=matches'));
      if (response.statusCode == 200) {
        List<dynamic> data = json.decode(response.body);
        return data.map((json) => MatchModel.fromJson(json)).toList();
      } else {
        throw Exception('Failed to load matches');
      }
    } catch (e) {
      throw Exception('Error connecting to API: $e');
    }
  }
}
