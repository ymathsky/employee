import React, { useState, useEffect } from 'react';
import { StyleSheet, Text, View, FlatList, ActivityIndicator, SafeAreaView, RefreshControl, StatusBar } from 'react-native';
import axios from 'axios';
import moment from 'moment';

// CONFIGURE YOUR API URL HERE
// For Android Emulator use 'http://10.0.2.2/employee/api/get_employee_daily_logs.php'
// For iOS Simulator use 'http://localhost/employee/api/get_employee_daily_logs.php'
// For Physical Device use your machine's IP address e.g., 'http://192.168.1.5/employee/api/get_employee_daily_logs.php'
const API_URL = 'http://10.0.2.2/employee/api/get_employee_daily_logs.php'; 

// Pre-defined employee ID for demo (In a real app, this would come from Login)
const DEMO_EMPLOYEE_ID = 2; // Change this to a valid employee ID from your DB

export default function App() {
  const [loading, setLoading] = useState(true);
  const [logs, setLogs] = useState([]);
  const [employee, setEmployee] = useState(null);
  const [refreshing, setRefreshing] = useState(false);

  const fetchLogs = async () => {
    try {
      // Fetching logs for current month by default
      const startDate = moment().startOf('month').format('YYYY-MM-DD');
      const endDate = moment().endOf('month').format('YYYY-MM-DD');
      
      const response = await axios.get(API_URL, {
        params: {
          employee_id: DEMO_EMPLOYEE_ID,
          start_date: startDate,
          end_date: endDate
        }
      });
      
      // The API returns strictly JSON now
      const data = response.data;
      if (data && data.logs) { // Check structure based on PHP output
          setLogs(data.logs);
          setEmployee(data.employee);
      } else {
          console.error("Invalid data format:", data);
      }
    } catch (error) {
      console.error(error);
      alert('Error fetching logs. Check console and API URL.');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useEffect(() => {
    fetchLogs();
  }, []);

  const onRefresh = () => {
    setRefreshing(true);
    fetchLogs();
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'Present': return '#10B981'; // Green
      case 'Absent': return '#EF4444'; // Red
      case 'Rest Day': return '#9CA3AF'; // Gray
      default: return '#3B82F6'; // Blue for Leaves
    }
  };

  const renderItem = ({ item }) => {
    const isPresent = item.status === 'Present';
    const hasSegments = item.segments && item.segments.length > 0;
    
    // Find first in and last out
    let firstIn = '-';
    let lastOut = '-';
    
    if (hasSegments) {
        firstIn = item.segments[0].time_in ? moment(item.segments[0].time_in).format('h:mm A') : '-';
        
        const lastSegment = item.segments[item.segments.length - 1];
        lastOut = lastSegment.time_out ? moment(lastSegment.time_out).format('h:mm A') : 'Active';
    }

    return (
      <View style={styles.card}>
        <View style={styles.cardHeader}>
          <Text style={styles.dateText}>{moment(item.log_date).format('ddd, MMM D, YYYY')}</Text>
          <View style={[styles.statusBadge, { backgroundColor: getStatusColor(item.status) }]}>
            <Text style={styles.statusText}>{item.status}</Text>
          </View>
        </View>

        <View style={styles.cardBody}>
          <View style={styles.row}>
            <View style={styles.col}>
              <Text style={styles.label}>Clock In</Text>
              <Text style={styles.value}>{firstIn}</Text>
            </View>
            <View style={styles.col}>
              <Text style={styles.label}>Clock Out</Text>
              <Text style={styles.value}>{lastOut}</Text>
            </View>
             <View style={styles.col}>
              <Text style={styles.label}>Total Hrs</Text>
              <Text style={[styles.value, { color: isPresent ? '#4F46E5' : '#6B7280' }]}>
                {item.total_hours ? item.total_hours + ' hrs' : '-'}
              </Text>
            </View>
          </View>

          {/* Expanded details (Deduction / Remarks) */}
           <View style={[styles.row, { marginTop: 10, borderTopWidth: 1, borderTopColor: '#F3F4F6', paddingTop: 8 }]}>
             <View style={styles.col}>
                <Text style={styles.label}>Deduction</Text>
                <Text style={[styles.value, { color: item.deduction_amount > 0 ? '#EF4444' : '#10B981' }]}>
                    {item.deduction_amount > 0 ? item.deduction_amount : '0.00'}
                </Text>
             </View>
              <View style={[styles.col, { flex: 2}]}>
                <Text style={styles.label}>Remarks</Text>
                 <Text style={styles.value} numberOfLines={1}>
                    {item.segments && item.segments.map(s => s.remarks).filter(Boolean).join(', ') || '-'}
                </Text>
             </View>
           </View>

        </View>
      </View>
    );
  };

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar style="auto" />
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Attendance Logs</Text>
        {employee && (
            <Text style={styles.subHeader}>
                {employee.first_name} {employee.last_name}
            </Text>
        )}
      </View>
      
      {loading ? (
        <ActivityIndicator size="large" color="#4F46E5" style={{ marginTop: 50 }} />
      ) : (
        <FlatList
          data={logs}
          renderItem={renderItem}
          keyExtractor={item => item.log_date}
          contentContainerStyle={styles.list}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
          }
        />
      )}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F3F4F6',
  },
  header: {
    backgroundColor: '#fff',
    padding: 20,
    paddingTop: 40, 
    borderBottomWidth: 1,
    borderBottomColor: '#E5E7EB',
    alignItems: 'center',
  },
  headerTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#111827',
  },
  subHeader: {
    fontSize: 14,
    color: '#6B7280',
    marginTop: 4,
  },
  list: {
    padding: 16,
  },
  card: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 16,
    marginBottom: 12,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 2,
    elevation: 2,
  },
  cardHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  dateText: {
    fontSize: 16,
    fontWeight: '600',
    color: '#374151',
  },
  statusBadge: {
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 12,
  },
  statusText: {
    color: '#fff',
    fontSize: 12,
    fontWeight: 'bold',
  },
  cardBody: {
    flexDirection: 'column',
  },
  row: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  col: {
    flex: 1,
  },
  label: {
    fontSize: 11,
    color: '#9CA3AF',
    textTransform: 'uppercase',
    marginBottom: 2,
  },
  value: {
    fontSize: 14,
    color: '#1F2937',
    fontWeight: '500',
  },
});
