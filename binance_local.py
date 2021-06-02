'''backtest
start: 2021-03-01 00:00:00
end: 2021-04-01 00:00:00
period: 15m
basePeriod: 30m
exchanges: [{"eid":"BINANCE","currency":"LTC_USDT","balance":500,"stocks":0}]
'''
import time
from pyecharts.charts import Bar
from binance.client import Client
from fmz import *
task = VCtx(__doc__) 

recent_value = -1  #avoid special case
rollback_rate = 0.05
buy_side_price = -1
deal_record = []
action_record = []


# 获取历史分钟级数据模拟回测数据
# 创建账户信息记录收益、损失，费率买入卖出0.001
# 统计指标，
# 获取指标判定
# 选择开盘价买入

class OffAccount:
    def __init__(self,init_balance,init_stocks):
        self.balance = init_balance
        self.stocks = init_stocks
    
    def g_account(self, price):
        return self.balance+price*self.stocks

    def buy(self, number, price):
        self.stocks += number
        self.binance -= number*price

    def sell(self, number, price):
        self.stocks -= number
        self.binance += number*price


class OlineAccount:
    def __init__(self,client, init_balance,init_stocks):
        self.client = client
        self.balance = init_balance
        self.stocks = init_stocks
    
    def g_account(self, price):
        return self.balance+price*self.stocks

    def buy(self, number, price):
        self.stocks += number
        self.binance -= number*price

    def sell(self, number, price):
        self.stocks -= number
        self.binance += number*price



class BinanceExchange:
    def __init__(self, status, init_balance, init_stocks, symbol, recall_start, recall_end=None):
        self.status = status
        self.fake_account = OffAccount(init_balance,init_stocks)
        api_key = ''
        api_secret = '' 
        self.client =  Client(api_key, api_secret)  
        self.symbol = symbol
        self.hist_k_line = self.client.get_historical_klines_generator(self.symbol, Client.KLINE_INTERVAL_1MINUTE, recall_start, recall_end)
        self.two_day_gap = 48 * 60 * 60 * 1000
        self.one_min_gap = 60 * 1000
    

    def get_ticker(self):
        ticker_info = ['Open_T','Open','High','Low','Close','Volume','Close_T', 'Quote', 'Num', '_V1','_V2','_V3']
        if self.status == 'offline':
            info_dict = dict(zip(ticker_info, next(self.hist_k_line)))
            recent_k_lines = [float(t[4]) for t in self.client.get_historical_klines(self.symbol, Client.KLINE_INTERVAL_15MINUTE, info_dict['Open_T']-self.two_day_gap, info_dict['Open_T']-self.one_min_gap)]
            # print(recent_k_lines)

            EMA_25 = TA.EMA(recent_k_lines, 25)[-1]
            EMA_99 = TA.EMA(recent_k_lines, 99)[-1]
            BOLL = TA.BOLL(recent_k_lines)
            info_dict.setdefault('EMA_25', EMA_25)
            info_dict.setdefault('EMA_99', EMA_99)
            Boll_Bound = [BOLL[0][-1], BOLL[1][-1], BOLL[2][-1]]
            Boll_Var_Rate = (BOLL[2][-1] - BOLL[0][-1]) / BOLL[1][-1]
            Boll_Bound.append(Boll_Var_Rate)
            info_dict.setdefault('BOLL', Boll_Bound)
            # info_dict.setdefault('Hist_T', recent_k_lines[0][1])
            return info_dict



    def boll_trade():
        global buy_side_price,deal_record,action_record
        print("BOLL状态"+str(BOLL[0][-1])+","+str(BOLL[1][-1])+","+str(BOLL[2][-1]))    
        # if(((recent_value - NowTicker.Last)/recent_value > rollback_rate) or (EMA_25 <= EMA_99)): 
        if (recent_value - NowTicker.Last)/recent_value > rollback_rate: 
        # if ((recent_value - NowTicker.Last)/recent_value > rollback_rate) or (EMA_25 < EMA_99) or (buy_side_price >= BOLL[0][-1]): 
            if((recent_value - NowTicker.Last)/recent_value > rollback_rate):
                print("紧急情况,股价骤跌")
            if((EMA_25 <= EMA_99)):
                print("紧急情况,下跌态势")            
            print("股价波动比例"+str((recent_value - NowTicker.Last)/recent_value))
            print("下行趋势"+str(EMA_25)+","+str(EMA_99))   
            if buy_side_price != -1:
                print("买入价",buy_side_price,"卖出",NowAsset.Stocks,"个币")
                # while 1:  #实际上线则全部卖掉然后待机
                exchange.Sell(NowTicker.Buy,NowAsset.Stocks)
                buy_side_price = -1        
                deal_record.append(NowTicker.Buy)
                action_record.append("下")

        else:
            if(NowTicker.Last < BOLL[2][-1] and NowTicker.Volume >= 2 and NowAsset.Balance > 0.1 and buy_side_price < 0):
                print("交易将被执行，以",NowTicker.Sell,"买入",NowAsset.Balance/NowTicker.Sell,"个币")
                exchange.SetPrecision(5,5) #设置计价精度
                exchange.Buy(NowTicker.Sell,NowAsset.Balance/NowTicker.Sell-0.01) #执行币买入交易 
                buy_side_price =  NowTicker.Sell 
                deal_record.append(NowTicker.Sell)
                action_record.append("上")            
                CancelAll()     
            elif(NowTicker.Last > BOLL[0][-1] and NowTicker.Volume >= 100  and NowAsset.Stocks > 0 and buy_side_price > 0):
                print("买入价",buy_side_price)
                print("交易将被执行，以",NowTicker.Buy,"卖出",NowAsset.Stocks,"个币")
                exchange.SetPrecision(5,5) #设置计价精度
                exchange.Sell(NowTicker.Buy,NowAsset.Stocks) #执行币卖出交易
                buy_side_price = -1
                deal_record.append(NowTicker.Buy)
                action_record.append("下")
                CancelAll()

            else:
                print("未触发交易条件")


    def log(self, content, stag):
        if stag == 'act':
            with open('logs/act.log','a') as f:
                f.write(content)
        elif stag == 'reve':
            with open('logs/reve.log','a') as f:
                f.write(content)

                


if __name__ == '__main__':
    # b_e = BinanceExchange("offline",10000,0,"BNBUSDT","1 Mar, 2021", "30 Mar, 2021")
    b_e = BinanceExchange("offline",10000,0,"BNBUSDT",1614556800000, 1615577800000)
    print(b_e.get_ticker())
    # print(b_e.get_ticker())
    # print(b_e.get_ticker())
    # print(b_e.get_ticker())
    # b_e_2 = BinanceExchange("offline",10000,0,"BNBUSDT",1614556800000, 1615577800000)




