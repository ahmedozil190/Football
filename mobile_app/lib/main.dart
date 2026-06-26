import 'package:flutter/material.dart';
import 'services/api_service.dart';
import 'screens/player_screen.dart';

void main() {
  runApp(const KooraProApp());
}

class KooraProApp extends StatelessWidget {
  const KooraProApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'كورة برو',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        brightness: Brightness.dark,
        primarySwatch: Colors.deepPurple,
        scaffoldBackgroundColor: const Color(0xFF0F172A),
      ),
      home: const HomeScreen(),
    );
  }
}

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  final ApiService _apiService = ApiService();
  late Future<List<MatchModel>> _matchesFuture;

  @override
  void initState() {
    super.initState();
    _matchesFuture = _apiService.fetchMatches();
  }

  Future<void> _refreshMatches() async {
    setState(() {
      _matchesFuture = _apiService.fetchMatches();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('كورة برو - البث المباشر', style: TextStyle(fontWeight: FontWeight.bold)),
        centerTitle: true,
        backgroundColor: const Color(0xFF1E293B),
        elevation: 0,
      ),
      body: RefreshIndicator(
        onRefresh: _refreshMatches,
        child: FutureBuilder<List<MatchModel>>(
          future: _matchesFuture,
          builder: (context, snapshot) {
            if (snapshot.connectionState == ConnectionState.waiting) {
              return const Center(child: CircularProgressIndicator());
            } else if (snapshot.hasError) {
              return Center(child: Text('خطأ: ${snapshot.error}'));
            } else if (!snapshot.hasData || snapshot.data!.isEmpty) {
              return const Center(child: Text('لا توجد مباريات حالياً'));
            }

            return ListView.builder(
              padding: const EdgeInsets.all(12),
              itemCount: snapshot.data!.length,
              itemBuilder: (context, index) {
                final match = snapshot.data![index];
                return _buildMatchCard(context, match);
              },
            );
          },
        ),
      ),
    );
  }

  Widget _buildMatchCard(BuildContext context, MatchModel match) {
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        color: const Color(0xFF1E293B),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.white12),
      ),
      child: InkWell(
        onTap: () {
          if (match.streamUrl.isNotEmpty) {
            Navigator.push(
              context,
              MaterialPageRoute(
                builder: (context) => PlayerScreen(
                  streamUrl: match.streamUrl,
                  title: '${match.homeTeam} vs ${match.awayTeam}',
                ),
              ),
            );
          } else {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(content: Text('البث غير متاح لهذه المباراة حالياً')),
            );
          }
        },
        borderRadius: BorderRadius.circular(16),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  _buildTeam(match.homeTeam, match.homeLogo),
                  Column(
                    children: [
                      Text(
                        match.status == 'live' ? '${match.awayScore} - ${match.homeScore}' : match.time,
                        style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
                      ),
                      const SizedBox(height: 4),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: match.status == 'live' ? Colors.red : Colors.grey,
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(match.statusText, style: const TextStyle(fontSize: 10)),
                      ),
                    ],
                  ),
                  _buildTeam(match.awayTeam, match.awayLogo),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildTeam(String name, String logo) {
    return Expanded(
      child: Column(
        children: [
          Image.network(logo, height: 40, width: 40, errorBuilder: (c, e, s) => const Icon(Icons.sports_soccer)),
          const SizedBox(height: 8),
          Text(name, textAlign: TextAlign.center, style: const TextStyle(fontSize: 12), maxLines: 1),
        ],
      ),
    );
  }
}
