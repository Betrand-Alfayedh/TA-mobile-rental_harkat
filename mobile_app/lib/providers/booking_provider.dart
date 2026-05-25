import 'dart:io';
import 'package:flutter/foundation.dart';
import '../core/services/api_service.dart';
import '../models/booking_model.dart';

enum BookingLoadState { initial, loading, loaded, error }

class BookingProvider extends ChangeNotifier {
  List<BookingModel> _bookings  = [];
  List<BookingModel> _riwayats  = [];
  BookingLoadState   _state     = BookingLoadState.initial;
  BookingLoadState   _riwState  = BookingLoadState.initial;
  BookingLoadState   _payState  = BookingLoadState.initial;
  String             _error     = '';
  Map<String, dynamic>? _estimate;

  List<BookingModel>    get bookings  => _bookings;
  List<BookingModel>    get riwayats  => _riwayats;
  BookingLoadState      get state     => _state;
  BookingLoadState      get riwState  => _riwState;
  BookingLoadState      get payState  => _payState;
  String                get error     => _error;
  Map<String, dynamic>? get estimateData => _estimate;

  bool get isLoading    => _state  == BookingLoadState.loading;
  bool get isPayLoading => _payState == BookingLoadState.loading;

  // ── Active bookings ────────────────────────────────────────
  Future<void> loadBookings() async {
    _state = BookingLoadState.loading;
    notifyListeners();

    final res = await ApiService.getBookings();
    if (res.success && res.data != null) {
      final list = res.data!['data'] as List<dynamic>;
      _bookings = list
          .map((e) => BookingModel.fromJson(e as Map<String, dynamic>))
          .toList();
      _state = BookingLoadState.loaded;
    } else {
      _error = res.message;
      _state = BookingLoadState.error;
    }
    notifyListeners();
  }

  // ── History ────────────────────────────────────────────────
  Future<void> loadRiwayat() async {
    _riwState = BookingLoadState.loading;
    notifyListeners();

    final res = await ApiService.getRiwayat();
    if (res.success && res.data != null) {
      final list = res.data!['data'] as List<dynamic>;
      _riwayats = list
          .map((e) => BookingModel.fromJson(e as Map<String, dynamic>))
          .toList();
      _riwState = BookingLoadState.loaded;
    } else {
      _error    = res.message;
      _riwState = BookingLoadState.error;
    }
    notifyListeners();
  }

  // ── Single booking ─────────────────────────────────────────
  Future<BookingModel?> getDetail(int id) async {
    final res = await ApiService.getBookingDetail(id);
    if (res.success && res.data != null) {
      return BookingModel.fromJson(res.data!['data'] as Map<String, dynamic>);
    }
    _error = res.message;
    notifyListeners();
    return null;
  }

  // ── Estimate price ─────────────────────────────────────────
  Future<bool> fetchEstimate(List<Map<String, dynamic>> mobils) async {
    _estimate = null;
    notifyListeners();

    final res = await ApiService.estimateBooking(mobils);
    if (res.success && res.data != null) {
      _estimate = res.data!['data'] as Map<String, dynamic>;
      notifyListeners();
      return true;
    }
    _error = res.message;
    notifyListeners();
    return false;
  }

  // ── Create booking ─────────────────────────────────────────
  Future<BookingModel?> createBooking(Map<String, dynamic> payload) async {
    _state = BookingLoadState.loading;
    notifyListeners();

    final res = await ApiService.createBooking(payload);
    if (res.success && res.data != null) {
      final booking =
          BookingModel.fromJson(res.data!['data'] as Map<String, dynamic>);
      _bookings.insert(0, booking);
      _state = BookingLoadState.loaded;
      notifyListeners();
      return booking;
    }

    _error = res.message;
    _state = BookingLoadState.error;
    notifyListeners();
    return null;
  }

  // ── Upload bukti bayar ─────────────────────────────────────
  Future<bool> uploadBukti({
    required int bookingId,
    required File imageFile,
    int metode = 3,
  }) async {
    _payState = BookingLoadState.loading;
    notifyListeners();

    final res = await ApiService.uploadBukti(
      bookingId:         bookingId,
      imageFile:         imageFile,
      metodePembayaran:  metode,
    );

    if (res.success) {
      _payState = BookingLoadState.loaded;
      // Refresh list so status updates
      await loadBookings();
      return true;
    }

    _error    = res.message;
    _payState = BookingLoadState.error;
    notifyListeners();
    return false;
  }

  void clearError() {
    _error = '';
    notifyListeners();
  }

  void clearEstimate() {
    _estimate = null;
    notifyListeners();
  }
}
