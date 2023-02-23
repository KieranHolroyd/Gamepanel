import type { AxiosInstance } from "axios";
import { useState } from "react";
import "./style.css";

type ProfilePageProps = {
  api: AxiosInstance;
};
type Profile = {};

export function ProfilePage(props: ProfilePageProps) {
  const [profile, setProfile] = useState<Profile | null>(null);
  const [window, setCurrentWindow] = useState<
    "activity" | "punishments" | "bans" | "audit"
  >("activity");
  const [error, setError] = useState(false);

  return (
    <>
      <div className="ml-16">
        <h2 className="p-2">My Profile</h2>
      </div>
    </>
  );
}
