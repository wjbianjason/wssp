'''backtest
start: 2021-03-01 00:00:00
end: 2021-04-01 00:00:00
period: 15m
basePeriod: 30m
exchanges: [{"eid":"BINANCE","currency":"LTC_USDT","balance":500,"stocks":0}]
'''
import time
# from pyecharts.charts import Bar
from binance.client import Client
from fmz import *
task = VCtx(__doc__) 

recent_value = -1  #avoid special case
rollback_rate = 0.05
buy_side_price = -1
close_trade = -1
# deal_record = []
# action_record = []


# 获取历史分钟级数据模拟回测数据
# 创建账户信息记录收益、损失，费率买入卖出0.001
# 统计指标，
# 获取指标判定
# 选择开盘价买入

class OffAccount:
    def __init__(self, symbol, init_balance,init_stocks):
        self.binance = init_balance
        self.stocks = init_stocks
        self.symbol = symbol
    
    def g_account(self, price):
        return self.binance+price*self.stocks

    def get_binance(self):
        return self.stocks

    def buy(self, price):
        number = self.binance/price 
        self.stocks +=  number 
        self.binance -= number*price
        return "true"

    def sell(self, number, price):
        self.stocks -= number
        self.binance += number*price
        self.log("Sell Total Money"+str(self.binance))

    def log(self, content):
        with open('logs/reve.log','a') as f:
            f.write(content)

class OlineAccount:
    def __init__(self,client, symbol, init_balance,init_stocks):
        self.client = client
        self.symbol = symbol  
        self.pre = 4     
    
    def get_usdt(self):
        dic = self.client.get_asset_balance(asset='USDT')
        return dic['free']

    def get_binance(self):
        dic = self.client.get_asset_balance(asset='BNB')
        return dic['free']

    def buy(self, price):
        number = round(self.get_usdt()/price, self.pre)
        order_id = self.client.order_limit_buy(
                    symbol=self.symbol,
                    quantity=number,
                    price='0.00001')
        return true
        # if order_id is None:
            # self.client.get_all_orders(symbol=self.symbol)

    def sell(self, number, price):
        number = self.get_binance()
        order_id = self.client.order_limit_sell(
                    symbol=self.symbol,
                    quantity=number,
                    price='0.00001')        

        self.log("Sell Total Money"+str(self.get_usdt()))        

    def log(self, content):
        with open('logs/reve.log','a') as f:
            f.write(content)


class BinanceExchange:
    def __init__(self, status, init_balance, init_stocks, symbol, recall_start, recall_end=None):
        self.status = status
        api_key = '9CuLKi24uTNSpfMbDo93MRZNcMzzGMgSfXI9CtXcru'
        api_secret = 'ZbiVrjFqdUs1KEJCmvuIJ2hjAut8LJzwU5jkGDR1aDwhnRguuxxCD2QdNluoRZBI' 
        self.client =  Client(api_key, api_secret) 
        self.symbol = symbol
        if status == "offline":
            self.hist_k_line = self.client.get_historical_klines_generator(self.symbol, Client.KLINE_INTERVAL_1MINUTE, recall_start, recall_end)            
            self.account = OffAccount(symbol, init_balance, init_stocks)
        elif status == "online":
            self.account = OlineAccount(self.client, init_balance, init_stocks)
        self.two_day_gap = 48 * 60 * 60 * 1000
        self.one_day_gap = 24 * 60 * 60 * 1000
        self.one_min_gap = 60 * 1000
    

    def get_ticker(self):
        if self.status == 'offline':
            ticker_info = ['Open_T','Open','High','Low','price','Volume','Close_T', 'Quote', 'Num', '_V1','_V2','_V3']            
            info_dict = dict(zip(ticker_info, next(self.hist_k_line)))
            recent_k_lines = [float(t[4]) for t in self.client.get_historical_klines(self.symbol, Client.KLINE_INTERVAL_15MINUTE, info_dict['Open_T']-self.one_day_gap, info_dict['Open_T']-self.one_min_gap)]
            # print(recent_k_lines)

            EMA_14 = TA.EMA(recent_k_lines, 14)[-1]
            EMA_60 = TA.EMA(recent_k_lines, 60)[-1]
            BOLL = TA.BOLL(recent_k_lines)
            info_dict.setdefault('EMA_14', EMA_14)
            info_dict.setdefault('EMA_60', EMA_60)
            Boll_Bound = [BOLL[0][-1], BOLL[1][-1], BOLL[2][-1]]
            Boll_Var_Rate = (BOLL[0][-1] - BOLL[2][-1]) / BOLL[1][-1]
            Boll_Bound.append(Boll_Var_Rate)
            info_dict.setdefault('BOLL', Boll_Bound)
            info_dict['price'] = float(info_dict['price'])
            # info_dict.setdefault('Hist_T', recent_k_lines[0][1])
            return info_dict
        elif self.status == "online":
            info_dict = client.get_recent_trades(symbol=self.symbol, limit=1)[0]
            recent_k_lines = [float(t[4]) for t in self.client.get_historical_klines(self.symbol, Client.KLINE_INTERVAL_15MINUTE, info_dict['time']-self.one_day_gap, info_dict['time']-self.one_min_gap)]
            EMA_14 = TA.EMA(recent_k_lines, 14)[-1]
            EMA_60 = TA.EMA(recent_k_lines, 60)[-1]
            BOLL = TA.BOLL(recent_k_lines)
            info_dict.setdefault('EMA_14', EMA_14)
            info_dict.setdefault('EMA_60', EMA_60)
            Boll_Bound = [BOLL[0][-1], BOLL[1][-1], BOLL[2][-1]]
            Boll_Var_Rate = (BOLL[0][-1] - BOLL[2][-1]) / BOLL[1][-1]
            Boll_Bound.append(Boll_Var_Rate)
            info_dict.setdefault('BOLL', Boll_Bound)
            info_dict['price'] = float(info_dict['price'])
            # info_dict.setdefault('Hist_T', recent_k_lines[0][1])
            return info_dict



    def boll_trade(self, ticker_info):
        global buy_side_price, rollback_rate
        # self.log("BOLL状态"+str(ticker_info['BOLL'])) 
        self.log("ticket状态"+str(ticker_info)) 

        # print(ticker_info)
        # print(buy_side_price)
        if ((((buy_side_price - ticker_info['price'])/buy_side_price > rollback_rate) or (ticker_info['EMA_14'] < ticker_info['EMA_60']) or (ticker_info['price'] > ticker_info['BOLL'][0]) ) and buy_side_price != -1): 
            
            if((buy_side_price - ticker_info['price'])/buy_side_price > rollback_rate):
                self.log("股价波动比例"+str((buy_side_price - ticker_info['price'])/buy_side_price))
                self.log("紧急情况,股价骤跌,卖出"+str(self.account.stocks))
                self.account.sell(self.account.get_binance(), ticker_info['price'])
                buy_side_price = -1  

            if(ticker_info['EMA_14'] < ticker_info['EMA_60']):
                self.log("紧急情况,下跌态势,卖出"+str(self.account.stocks))
                self.account.sell(self.account.get_binance(), ticker_info['price'])
                buy_side_price = -1  

            if(ticker_info['price'] > ticker_info['BOLL'][0]):
                self.log("BOLL上界,卖出"+str(self.account.stocks))
                self.account.sell(self.account.get_binance(), ticker_info['price'])
                buy_side_price = -1 

        else:
            if(ticker_info['price'] < ticker_info['BOLL'][2] and ticker_info['BOLL'][3] > 0.04  and buy_side_price < 0 and (ticker_info['EMA_14'] > ticker_info['EMA_60'])):
                self.log("交易将被执行，以"+str(ticker_info['price'])+"买入")
                stag = self.account.buy(ticker_info['price'])
                if stag:
                    buy_side_price = ticker_info['price']
            else:
                self.log("未触发交易条件")


    def log(self, content):
        with open('logs/act.log','w') as f:
            f.write(content+"\n")


    def trade_iterator(self):
        if self.status == "offline":
            for i in range(1000):
                time.sleep(0.1)
                ticker_info = self.get_ticker()
                self.boll_trade(ticker_info)
        elif self.status == "online":
            while true:
                time.sleep(1)
                ticker_info = self.get_ticker()
                self.boll_trade(ticker_info)

if __name__ == '__main__':
    # b_e = BinanceExchange("offline",10000,0,"BNBUSDT","1 Mar, 2021", "30 Mar, 2021")
    b_e = BinanceExchange("offline",10000,0,"BNBUSDT",1612622774000, 1626622774000)
    b_e.trade_iterator()



    # print(b_e.get_ticker())
    # print(b_e.get_ticker())
    # print(b_e.get_ticker())
    # print(b_e.get_ticker())
    # b_e_2 = BinanceExchange("offline",10000,0,"BNBUSDT",1614556800000, 1615577800000)




