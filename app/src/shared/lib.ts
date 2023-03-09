import type { AxiosInstance } from "axios";
import type PusherJS from "pusher-js";

export type Player = {
  id: string;
  type: string;
  name: string;
  guid: string;
  case_id?: string;
};

export type APIClient = AxiosInstance;
export type PusherClient = PusherJS;
